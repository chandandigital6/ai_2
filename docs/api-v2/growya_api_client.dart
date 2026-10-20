// Mobile/desktop transport example for the documented Growya API.
// Uses dart:io. Flutter web needs a web-capable HTTP transport instead.
// Supply token callbacks backed by the app's secure platform storage.
import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

typedef ReadToken = Future<String?> Function();
typedef ClearToken = Future<void> Function();

class ApiReply {
  final int status;
  final dynamic json;
  final Uint8List bytes;
  final String? contentType;
  final String? contentDisposition;
  const ApiReply(this.status, this.json, this.bytes,
      this.contentType, this.contentDisposition);
}

class GrowyaApiError implements Exception {
  final int status;
  final String message;
  final Map<String, dynamic> errors;
  final dynamic payload;
  const GrowyaApiError(this.status, this.message, this.errors, this.payload);
  @override
  String toString() => 'GrowyaApiError($status, $message)';
}

class UploadPart {
  final String field;
  final String localPath;
  final String mimeType;
  const UploadPart(this.field, this.localPath,
      {this.mimeType = 'application/octet-stream'});
}

class GrowyaApiClient {
  final Uri baseUrl;
  final ReadToken readToken;
  final ClearToken clearToken;
  final HttpClient _http;
  final Duration timeout;

  GrowyaApiClient({
    required String baseUrl,
    required this.readToken,
    required this.clearToken,
    HttpClient? httpClient,
    this.timeout = const Duration(seconds: 30),
  })  : baseUrl = Uri.parse('${baseUrl.replaceAll(RegExp(r'/+$'), '')}/'),
        _http = httpClient ?? HttpClient() {
    if (this.baseUrl.scheme != 'https') {
      throw ArgumentError('Use the HTTPS API base URL ending in /api/v1.');
    }
    _http.connectionTimeout = timeout;
  }

  Uri uri(String path, [Map<String, String>? query]) {
    // A relative path keeps /api/v1 rather than replacing the URI root.
    final result = baseUrl.resolve(path.replaceFirst(RegExp(r'^/+'), ''));
    return query == null ? result : result.replace(queryParameters: query);
  }

  Future<void> _headers(HttpClientRequest request, bool auth) async {
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    if (auth) {
      final token = await readToken();
      if (token == null || token.isEmpty) {
        request.abort();
        throw StateError('Sign in before a protected API request.');
      }
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
    }
  }

  Future<ApiReply> _read(HttpClientResponse response) async {
    final builder = await response.fold<BytesBuilder>(
      BytesBuilder(copy: false),
      (buffer, chunk) => buffer..add(chunk),
    );
    final bytes = builder.takeBytes();
    dynamic payload;
    final type = response.headers.contentType?.mimeType;
    if ((type ?? '').contains('json') && bytes.isNotEmpty) {
      payload = jsonDecode(utf8.decode(bytes));
    }
    if (response.statusCode == 401) await clearToken();
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final map = payload is Map ? Map<String, dynamic>.from(payload) : <String, dynamic>{};
      final fields = map['errors'] is Map
          ? Map<String, dynamic>.from(map['errors']) : <String, dynamic>{};
      throw GrowyaApiError(response.statusCode,
          map['message']?.toString() ?? 'HTTP ${response.statusCode}', fields, payload);
    }
    return ApiReply(response.statusCode, payload, bytes, type,
        response.headers.value(HttpHeaders.contentDispositionHeader));
  }

  Future<ApiReply> request(String method, String path, {
    Map<String, String>? query,
    Map<String, dynamic>? body,
    bool auth = true,
  }) async {
    final request = await _http.openUrl(method, uri(path, query)).timeout(timeout);
    // Expose an accidental web redirect instead of following it with mobile state.
    request.followRedirects = false;
    await _headers(request, auth);
    if (body != null) {
      request.headers.contentType = ContentType.json;
      request.write(jsonEncode(body));
    }
    final response = await request.close().timeout(timeout);
    return _read(response).timeout(timeout);
  }

  Future<ApiReply> upload(String path, {
    Map<String, String> fields = const {},
    List<UploadPart> files = const [],
    bool auth = true,
  }) async {
    final boundary = 'growya_${DateTime.now().microsecondsSinceEpoch}';
    final request = await _http.postUrl(uri(path)).timeout(timeout);
    request.followRedirects = false;
    await _headers(request, auth);
    request.headers.contentType = ContentType('multipart', 'form-data',
        parameters: {'boundary': boundary});
    String safe(String value) => value.replaceAll(RegExp(r'[\r\n"]'), '');
    void text(String value) => request.add(utf8.encode(value));
    for (final entry in fields.entries) {
      text('--$boundary\r\nContent-Disposition: form-data; '
          'name="${safe(entry.key)}"\r\n\r\n${entry.value}\r\n');
    }
    for (final part in files) {
      final file = File(part.localPath);
      final filename = safe(file.uri.pathSegments.last);
      text('--$boundary\r\nContent-Disposition: form-data; '
          'name="${safe(part.field)}"; filename="$filename"\r\n'
          'Content-Type: ${safe(part.mimeType)}\r\n\r\n');
      await request.addStream(file.openRead());
      text('\r\n');
    }
    text('--$boundary--\r\n');
    return _read(await request.close().timeout(timeout)).timeout(timeout);
  }

  void close() => _http.close(force: true);
}

// Integration sketches; replace the storage callbacks and values in your app:
//
// final api = GrowyaApiClient(
//   baseUrl: 'https://YOUR_DOMAIN/api/v1',
//   readToken: tokenStore.read,
//   clearToken: tokenStore.clear,
// );
// final login = await api.request('POST', 'auth/login', auth: false, body: {
//   'email': email, 'password': password, 'device_name': 'Flutter Android',
// });
// await tokenStore.write(login.json['data']['token'] as String);
//
// final result = await api.request('GET', 'businesses/$businessId/products',
//     query: {'page': '1', 'search': 'ring'});
// final rows = result.json['data']['products']['data'] as List;
// final permissionResult = await api.request('GET',
//     'businesses/$businessId/my-permissions');
// final permissions = List<String>.from(permissionResult.json['data']);
//
// await api.upload('businesses/$businessId/products', fields: {
//   'name': 'Gold Ring', 'price': '25000', 'stock_status': 'in_stock',
//   'stock_quantity': '5', 'status': 'active', 'is_featured': '0',
//   'is_trending': '0',
// }, files: [UploadPart('featured_image', '/local/ring.jpg', mimeType: 'image/jpeg')]);
//
// For a file update to a PUT route, send multipart POST with fields['_method']='PUT'.
// For private downloads, call their API path, then save reply.bytes using the
// platform's file picker/storage policy. Do not follow a session-auth web URL.
// API read shapes differ: chat poll uses reply.json['messages'] directly.
// Login errors may be 422. Business permission errors are 403, not token expiry.
// There is no refresh endpoint and this client does not auto-retry POST writes.
