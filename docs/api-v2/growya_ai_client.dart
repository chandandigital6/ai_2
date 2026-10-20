// API-only wrapper for Flutter mobile/desktop. Import with growya_api_client.dart.
// Examples use dart:io transport. Adapt HTTP transport for Flutter web.
// Never ship an OpenAI key or super-admin token inside the owner app.
import 'dart:async';
import 'dart:typed_data';
import 'growya_api_client.dart';

class GrowyaAiClient {
  final GrowyaApiClient api;
  final int businessId;
  const GrowyaAiClient(this.api, this.businessId);
  String get path => 'businesses/$businessId/ai';

  Future<Map<String, dynamic>> _data(Future<ApiReply> reply) async =>
      Map<String, dynamic>.from((await reply).json['data'] as Map);

  Future<Map<String, dynamic>> packages() => _data(api.request('GET', '$path/plans'));
  Future<Map<String, dynamic>> requestPackage({required int planId,
      required String requestKey, String? paymentReference, String? note}) =>
      _data(api.request('POST', '$path/plan-requests', body: {
        'ai_plan_id': planId, 'request_key': requestKey,
        if (paymentReference != null) 'payment_reference': paymentReference,
        if (note != null) 'note': note,
      }));
  Future<Map<String, dynamic>> brand() => _data(api.request('GET', '$path/brand'));
  Future<Map<String, dynamic>> saveBrand(Map<String, dynamic> fields) =>
      _data(api.request('PUT', '$path/brand', body: fields));
  Future<Map<String, dynamic>> uploadImage({required String localPath,
      required String kind, required String label, required String mimeType}) =>
      _data(api.upload('$path/assets', fields: {'kind': kind, 'label': label},
        files: [UploadPart('image', localPath, mimeType: mimeType)]));
  Future<Map<String, dynamic>> savePreset(Map<String, dynamic> fields, {int? id}) =>
      _data(api.request(id == null ? 'POST' : 'PUT',
        id == null ? '$path/presets' : '$path/presets/$id', body: fields));
  Future<Map<String, dynamic>> deletePreset(int id) =>
      _data(api.request('DELETE', '$path/presets/$id'));
  Future<Map<String, dynamic>> deleteAsset(int id) =>
      _data(api.request('DELETE', '$path/assets/$id'));
  Future<Map<String, dynamic>> generate({required String generationType,
      required int packageId, required String requestKey, String? prompt,
      int? presetId, String? size, String? language, List<int>? referenceIds,
      bool varyDesign = true}) => _data(api.request('POST', path, body: {
        'generation_type': generationType, 'plan_request_id': packageId,
        'request_key': requestKey, 'vary_design': varyDesign,
        if (prompt != null && prompt.trim().isNotEmpty) 'prompt': prompt,
        if (presetId != null) 'preset_id': presetId,
        if (size != null) 'size': size,
        if (language != null) 'language': language,
        // null = saved defaults; [] = no references except the business logo.
        if (referenceIds != null) 'reference_ids': referenceIds,
      }));
  Future<Map<String, dynamic>> status(int generationId) =>
      _data(api.request('GET', '$path/generations/$generationId'));
  Future<Map<String, dynamic>> credits() => _data(api.request('GET', '$path/credits'));
  Future<Uint8List> mediaBytes(int mediaId, {bool inline = true}) async =>
      (await api.request('GET', '$path/media/$mediaId',
        query: inline ? {'inline': '1'} : null)).bytes;
  Future<Uint8List> assetBytes(int assetId) async =>
      (await api.request('GET', '$path/assets/$assetId')).bytes;

  // Stop/cancel polling from the screen lifecycle. On transient errors display
  // GrowyaApiError and offer status retry; never blindly resubmit generation.
  Stream<Map<String, dynamic>> watch(int generationId,
      {bool Function()? isCancelled}) async* {
    while (!(isCancelled?.call() ?? false)) {
      final current = await status(generationId);
      yield current;
      if (current['status'] == 'completed' || current['status'] == 'failed') return;
      await Future<void>.delayed(const Duration(seconds: 4));
    }
  }
}
