> 26-Oct-2026 routing update: API routes now point to dedicated Api/V1 controllers. See docs/API_FUNCTIONS_GUIDE_HINDI.md and docs/API_ROUTE_CONTROLLER_MAP.md for current paths. Request/response contracts remain compatible.

> Release 3 AI update: use [AI_STUDIO_API.md](AI_STUDIO_API.md) for current AI models, packages, credits, presets and responses. Older AI sections below are superseded.

# New modules API — release 2

20 October 2026 · 59 new route templates, 411 total API v1 templates.

Base URL: `https://YOUR_DOMAIN/api/v1`. Protected requests: `Authorization: Bearer TOKEN`, `Accept: application/json`. JSON writes use `Content-Type: application/json`; file actions use multipart. Controllers return native JSON listed below; existing compatibility-adapter routes keep their previous response formats. Missing configuration returns 503 rather than mock success.

Use new UUID `request_key` values for new AI generations, credit grants, gateway attempts and journals; retain the same key on retries. Do not automatically retry other writes. Native lists generally paginate at `data`, so list rows are `data.data`; AI index instead returns `data.generations.data`.

Read `../PENDING_MODULES_SETUP_HINDI.md` for installation, credentials and scope boundaries. `api-v2/Growya_Full_API_Reference.html` and `api-v2/Full_API_Reference.md` cover all old and new endpoints.

## Permissions

| Module | Read | Write |
|---|---|---|
| AI | ai.view | ai.use |
| Social accounts/posts/schedules | social.view | social.manage |
| Commerce policies | commerce.view | commerce.manage |
| Accounting | accounting.view | accounting.manage |
| Delivery | delivery.view | delivery.manage |
| Gateway payments | payments.view | payments.manage |
| Analytics | analytics.view | Captured through validated events |

AI credit grants require platform ai.credits.manage. Admin tools require their per-kind platform permission. Manager/staff role labels alone grant nothing. Both participant/record ownership checks and active approved business checks remain enforced. Customer devices, history, reviews and order payments belong to the logged-in customer.

## Flows

1. OTP: send phone code → verify phone/code/device_name (plus authenticator code where needed) → store data.token. Existing account required; register new users with the existing auth/register endpoint.
2. FCM: request device notification permission in the app → obtain Firebase token → POST devices → refresh registration on token rotation → acknowledge delivery_id from the push payload → unregister on logout. accepted means provider acceptance; delivered/opened require app acknowledgement.
3. AI: read wallet → platform grant if needed → submit prompt/type/request_key → poll generation → show output_json.text or download protected media. Failure refunds the reserved credits exactly once.
4. Social: connect → open authorization_url in browser → after callback poll candidates with connection_key → select page_id/platform → create draft/now/scheduled post → poll post/platform results. Instagram requires a professional account and a single JPEG. Token expiry/revocation requires reconnect.
5. Checkout: POST shops/{business}/quote with state or owned address_id and optional coupon_code → show subtotal/discount/shipping/GST/total → existing checkout request includes quoted_total_paise → order snapshot persists exact amounts. Existing checkout placement returns 200 via the JSON compatibility adapter.
6. Razorpay: create payment order with request_key → use returned public key_id, amount_paise/currency/provider_order_id in Flutter SDK → POST SDK success fields to verify → show paid only from server result. API keys remain on server. Webhook uses raw-body HMAC and server fetch checks. Refund execution is a manual Razorpay-operator step in this release.
7. Delivery: send customer OTP → verify once (max 5 attempts) → upload private proof/recipient_name if required → complete. Legacy delivered actions enforce the same policy.
8. Accounting: create scoped chart of accounts → post balanced integer-paise lines → query reports → correct with reversal. Journals are immutable and independent from the older basic-finance ledger.

## Admin tool variants

`kind` is banners, festivals, settings, reports or audit. Use separate payloads for the selected kind.

```json
{"group_name":"branding","key_name":"site_title","value":"Growya","is_public":true}
```

```json
{"name":"Opening celebration","festival_date":"2026-11-01","default_caption":"Celebrate with us.","status":"active"}
```

```json
{"status":"resolved"}
```

The first payload is a settings create/update, the second a festival create/update, the third a reports update. Banner requests require image multipart plus placement, sort_order and status. Audit is read-only. Setting groups/keys are restricted to non-secret presentation values.

## Endpoint reference

### POST /shops/{business}/quote

Access: Authenticated. Permission: `controller ownership checks`. Rate: 30,1 (requests, minutes).

returns {data:{subtotal_paise,discount_paise,shipping_paise,tax_paise,total_paise,snapshot}}. Read before checkout. Checkout accepts optional quoted_total_paise and rejects changed totals before stock/order writes. Existing checkout place action returns HTTP 200 via compatibility adapter. Saved address state takes precedence over a supplied manual state.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `address_id` | integer | Optional | `['nullable', 'integer', Rule::exists('user_addresses', 'id')->where('user_id', $r->user()->id)]` |
| `state` | string | Conditional | `['required_without:address_id', 'nullable', 'string', 'max:100']` |
| `coupon_code` | string | Optional | `['nullable', 'string', 'max:40']` |

Source: `app/Http/Controllers/Extensions/QuoteController.php:20`. See full reference for conditional branches and example payloads.

### POST /auth/otp/send

Access: Public. Permission: `controller ownership checks`. Rate: 3,10 (requests, minutes).

send returns {message,expires_in}; verify returns {data:{token,token_type,expires_in,user}}. Invalid/expired/used code or ineligible account returns 422. Registration remains the existing /auth/register flow.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `phone` | string | Required | `['required', 'regex:/^[6-9][0-9]{9}$/']` |

Source: `app/Http/Controllers/Extensions/OtpController.php:17`. See full reference for conditional branches and example payloads.

### POST /auth/otp/verify

Access: Public. Permission: `controller ownership checks`. Rate: 10,10 (requests, minutes).

send returns {message,expires_in}; verify returns {data:{token,token_type,expires_in,user}}. Invalid/expired/used code or ineligible account returns 422. Registration remains the existing /auth/register flow.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `phone` | string | Required | `['required', 'regex:/^[6-9][0-9]{9}$/']` |
| `code` | string | Required | `['required', 'digits:6']` |
| `device_name` | string | Required | `['required', 'string', 'max:100']` |
| `two_factor_code` | string | Optional | `['nullable', 'string', 'max:20']` |

Source: `app/Http/Controllers/Extensions/OtpController.php:41`. See full reference for conditional branches and example payloads.

### GET /devices

Access: Authenticated. Permission: `controller ownership checks`. Rate: 60,1 (requests, minutes).

index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.


Source: `app/Http/Controllers/Extensions/DeviceController.php:12`. See full reference for conditional branches and example payloads.

### POST /devices

Access: Authenticated. Permission: `controller ownership checks`. Rate: 20,1 (requests, minutes).

index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `fcm_token` | string | Required | `['required', 'string', 'min:20', 'max:4096']` |
| `platform` | string | Required | `['required', Rule::in(['android', 'ios', 'web'])]` |
| `app_version` | string | Optional | `['nullable', 'string', 'max:40']` |

Source: `app/Http/Controllers/Extensions/DeviceController.php:17`. See full reference for conditional branches and example payloads.

### DELETE /devices/{device}

Access: Authenticated. Permission: `controller ownership checks`. Rate: 30,1 (requests, minutes).

index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.


Source: `app/Http/Controllers/Extensions/DeviceController.php:34`. See full reference for conditional branches and example payloads.

### POST /push-deliveries/{delivery}/ack

Access: Authenticated. Permission: `controller ownership checks`. Rate: 120,1 (requests, minutes).

index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `event` | string | Required | `['required', Rule::in(['delivered', 'opened'])]` |

Source: `app/Http/Controllers/Extensions/DeviceController.php:41`. See full reference for conditional branches and example payloads.

### POST /activity-events

Access: Authenticated. Permission: `controller ownership checks`. Rate: 30,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `resource_type` | string | Required | `['required', Rule::in(['business', 'product', 'service', 'offer'])]` |
| `resource_id` | integer | Required | `['required', 'integer', 'min:1']` |
| `event_type` | string | Required | `['required', Rule::in(['view', 'call_click', 'whatsapp_click', 'map_click', 'share'])]` |

Source: `app/Http/Controllers/Extensions/ActivityController.php:17`. See full reference for conditional branches and example payloads.

### GET /recently-viewed

Access: Authenticated. Permission: `controller ownership checks`. Rate: 60,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.


Source: `app/Http/Controllers/Extensions/ActivityController.php:25`. See full reference for conditional branches and example payloads.

### DELETE /recently-viewed

Access: Authenticated. Permission: `controller ownership checks`. Rate: 10,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.


Source: `app/Http/Controllers/Extensions/ActivityController.php:41`. See full reference for conditional branches and example payloads.

### POST /share-links

Access: Authenticated. Permission: `controller ownership checks`. Rate: 20,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `resource_type` | string | Required | `['required', Rule::in(['business', 'product', 'service', 'offer'])]` |
| `resource_id` | integer | Required | `['required', 'integer', 'min:1']` |

Source: `app/Http/Controllers/Extensions/ActivityController.php:48`. See full reference for conditional branches and example payloads.

### GET /share-links/{code}

Access: Public. Permission: `controller ownership checks`. Rate: 60,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.


Source: `app/Http/Controllers/Extensions/ActivityController.php:57`. See full reference for conditional branches and example payloads.

### GET /discovery/search

Access: Public. Permission: `controller ownership checks`. Rate: 30,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `query` | string | Required | `['required', 'string', 'max:120']` |
| `city_id` | integer | Optional | `['nullable', 'integer', 'exists:cities,id']` |

Source: `app/Http/Controllers/Extensions/ActivityController.php:78`. See full reference for conditional branches and example payloads.

### GET /content

Access: Public. Permission: `controller ownership checks`. Rate: 60,1 (requests, minutes).

kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `placement` | string | Optional | `['nullable', Rule::in(['home', 'marketplace', 'showroom'])]` |
| `city_id` | integer | Optional | `['nullable', 'integer', 'exists:cities,id']` |

Source: `app/Http/Controllers/Extensions/AdminToolsController.php:109`. See full reference for conditional branches and example payloads.

### POST /reports

Access: Authenticated. Permission: `controller ownership checks`. Rate: 5,1 (requests, minutes).

kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `resource_type` | string | Required | `['required', Rule::in(['business', 'product', 'service', 'offer'])]` |
| `resource_id` | integer | Required | `['required', 'integer', 'min:1']` |
| `reason` | string | Required | `['required', 'string', 'min:10', 'max:3000']` |

Source: `app/Http/Controllers/Extensions/AdminToolsController.php:100`. See full reference for conditional branches and example payloads.

### POST /reviews/{review}/photos

Access: Authenticated. Permission: `controller ownership checks`. Rate: 10,1 (requests, minutes).

store returns {data:{images:[{id,url}]}} (201), destroy {data:{removed:true}}; file returns streamed bytes. Uploads require review ownership, max 5 photos total, 3072 KB each. Upload resets approval to pending. Public image reads are approved-only; an owner may use bearer auth to read pending images.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `images` | array | Required | `['required', 'array', 'min:1', 'max:5']` |
| `images.*` | file | Required | `['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']` |

Source: `app/Http/Controllers/Extensions/ReviewPhotoController.php:14`. See full reference for conditional branches and example payloads.

### DELETE /reviews/{review}/photos/{photo}

Access: Authenticated. Permission: `controller ownership checks`. Rate: 20,1 (requests, minutes).

store returns {data:{images:[{id,url}]}} (201), destroy {data:{removed:true}}; file returns streamed bytes. Uploads require review ownership, max 5 photos total, 3072 KB each. Upload resets approval to pending. Public image reads are approved-only; an owner may use bearer auth to read pending images.


Source: `app/Http/Controllers/Extensions/ReviewPhotoController.php:55`. See full reference for conditional branches and example payloads.

### GET /review-photos/{photo}

Access: Public; bearer optional for pending private photos. Permission: `controller ownership checks`. Rate: 120,1 (requests, minutes).

store returns {data:{images:[{id,url}]}} (201), destroy {data:{removed:true}}; file returns streamed bytes. Uploads require review ownership, max 5 photos total, 3072 KB each. Upload resets approval to pending. Public image reads are approved-only; an owner may use bearer auth to read pending images.


Source: `app/Http/Controllers/Extensions/ReviewPhotoController.php:41`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/analytics/daily

Access: Authenticated. Permission: `analytics.view`. Rate: 60,1 (requests, minutes).

history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `from` | string | Optional | `['nullable', 'date_format:Y-m-d']` |
| `to` | string | Optional | `['nullable', 'date_format:Y-m-d', 'after_or_equal:from']` |

Source: `app/Http/Controllers/Extensions/ActivityController.php:69`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/ai

Access: Authenticated. Permission: `ai.view`. Rate: 60,1 (requests, minutes).

index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.


Source: `app/Http/Controllers/Extensions/AiController.php:19`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/ai

Access: Authenticated. Permission: `ai.use`. Rate: 5,1 (requests, minutes).

index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `generation_type` | file | Required | `['required', Rule::in(['text', 'image'])]` |
| `prompt` | string | Required | `['required', 'string', 'min:5', 'max:8000']` |
| `request_key` | string | Required | `['required', 'string', 'max:100']` |

Source: `app/Http/Controllers/Extensions/AiController.php:26`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/ai/generations/{generation}

Access: Authenticated. Permission: `ai.view`. Rate: 60,1 (requests, minutes).

index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.


Source: `app/Http/Controllers/Extensions/AiController.php:53`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/ai/media/{media}

Access: Authenticated. Permission: `ai.view`. Rate: 60,1 (requests, minutes).

index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.


Source: `app/Http/Controllers/Extensions/AiController.php:63`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/ai/credits

Access: Authenticated. Permission: `ai.view`. Rate: 60,1 (requests, minutes).

index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.


Source: `app/Http/Controllers/Extensions/AiController.php:72`. See full reference for conditional branches and example payloads.

### POST /admin/businesses/{business}/ai/credits

Access: Authenticated. Permission: `ai.credits.manage`. Rate: 20,1 (requests, minutes).

index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `credits` | integer | Required | `['required', 'integer', 'min:1', 'max:1000000']` |
| `request_key` | string | Required | `['required', 'string', 'max:100']` |

Source: `app/Http/Controllers/Extensions/AiController.php:79`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/social/accounts

Access: Authenticated. Permission: `social.view`. Rate: 60,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:24`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/social/connect

Access: Authenticated. Permission: `social.manage`. Rate: 5,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:31`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/social/candidates

Access: Authenticated. Permission: `social.manage`. Rate: 30,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `connection_key` | string | Required | `['required', 'string', 'size:64']` |

Source: `app/Http/Controllers/Extensions/SocialController.php:82`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/social/select

Access: Authenticated. Permission: `social.manage`. Rate: 10,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `connection_key` | string | Required | `['required', 'string', 'size:64']` |
| `page_id` | string | Required | `['required', 'string', 'max:100']` |
| `platform` | string | Required | `['required', Rule::in(['facebook', 'instagram'])]` |

Source: `app/Http/Controllers/Extensions/SocialController.php:91`. See full reference for conditional branches and example payloads.

### DELETE /businesses/{business}/social/accounts/{account}

Access: Authenticated. Permission: `social.manage`. Rate: 20,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:109`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/social/posts

Access: Authenticated. Permission: `social.view`. Rate: 60,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:118`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/social/posts

Access: Authenticated. Permission: `social.manage`. Rate: 10,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `caption` | string | Required | `['required', 'string', 'max:2200']` |
| `account_ids` | array | Required | `['required', 'array', 'min:1', 'max:10']` |
| `account_ids.*` | integer | Required | `['required', 'integer', 'distinct', Rule::exists('social_accounts', 'id')->where('business_id', $business->id)->where('connection_status', 'connected')]` |
| `image` | file | Optional | `['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192']` |
| `publish_mode` | string | Required | `['required', Rule::in(['draft', 'now', 'scheduled'])]` |
| `scheduled_at` | date | Conditional | `['required_if:publish_mode,scheduled', 'nullable', 'date', 'after:now']` |
| `request_key` | string | Required | `['required', 'string', 'max:100']` |
| `media_type` | file | Optional | `'image'` |

Source: `app/Http/Controllers/Extensions/SocialController.php:125`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/social/posts/{post}/publish

Access: Authenticated. Permission: `social.manage`. Rate: 10,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:174`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/social/posts/{post}/cancel

Access: Authenticated. Permission: `social.manage`. Rate: 10,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:188`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/social/schedules

Access: Authenticated. Permission: `social.view`. Rate: 60,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:202`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/social/schedules

Access: Authenticated. Permission: `social.manage`. Rate: 10,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `social_account_id` | integer | Required | `['required', 'integer', Rule::exists('social_accounts', 'id')->where('business_id', $business->id)->where('connection_status', 'connected')]` |
| `frequency` | string | Required | `['required', Rule::in(['daily', 'weekly'])]` |
| `run_time` | string | Required | `['required', 'date_format:H:i']` |
| `days_of_week` | array | Conditional | `['required_if:frequency,weekly', 'nullable', 'array', 'min:1', 'max:7']` |
| `days_of_week.*` | integer | Optional | `['integer', 'between:0,6', 'distinct']` |
| `timezone` | string | Required | `['required', 'timezone']` |
| `caption` | string | Required | `['required', 'string', 'max:2200']` |
| `image` | file | Optional | `['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192']` |

Source: `app/Http/Controllers/Extensions/SocialController.php:209`. See full reference for conditional branches and example payloads.

### DELETE /businesses/{business}/social/schedules/{schedule}

Access: Authenticated. Permission: `social.manage`. Rate: 20,1 (requests, minutes).

accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.


Source: `app/Http/Controllers/Extensions/SocialController.php:239`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/commerce-policy

Access: Authenticated. Permission: `commerce.view`. Rate: 60,1 (requests, minutes).

show/save return {data:policy|null}. API amounts are integer paise; tax_basis_points=300 means 3.00%. Web forms accept rupees/percent and convert server side. Defaults disable shipping/GST/OTP/POD. GST uses one configured exclusive business rate, after discount and including shipping; no rate is inferred.


Source: `app/Http/Controllers/Extensions/CommerceController.php:13`. See full reference for conditional branches and example payloads.

### PUT /businesses/{business}/commerce-policy

Access: Authenticated. Permission: `commerce.manage`. Rate: 10,1 (requests, minutes).

show/save return {data:policy|null}. API amounts are integer paise; tax_basis_points=300 means 3.00%. Web forms accept rupees/percent and convert server side. Defaults disable shipping/GST/OTP/POD. GST uses one configured exclusive business rate, after discount and including shipping; no rate is inferred.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `shipping_enabled` | boolean | Required | `['required', 'boolean']` |
| `shipping_paise` | integer | Required | `['required', 'integer', 'min:0', 'max:10000000']` |
| `free_above_paise` | integer | Optional | `['nullable', 'integer', 'min:0', 'max:1000000000']` |
| `tax_enabled` | boolean | Required | `['required', 'boolean']` |
| `tax_basis_points` | integer | Required | `['required', 'integer', 'min:0', 'max:4000']` |
| `seller_state` | string | Conditional | `['required_if:tax_enabled,1', 'nullable', 'string', 'exists:states,name']` |
| `delivery_otp_required` | boolean | Required | `['required', 'boolean']` |
| `delivery_proof_required` | boolean | Required | `['required', 'boolean']` |

Source: `app/Http/Controllers/Extensions/CommerceController.php:20`. See full reference for conditional branches and example payloads.

### POST /my-orders/{order}/gateway

Access: Authenticated. Permission: `controller ownership checks`. Rate: 5,1 (requests, minutes).

create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `request_key` | string | Required | `['required', 'string', 'max:100']` |

Source: `app/Http/Controllers/Extensions/GatewayController.php:13`. See full reference for conditional branches and example payloads.

### POST /my-orders/{order}/gateway/verify

Access: Authenticated. Permission: `controller ownership checks`. Rate: 10,1 (requests, minutes).

create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `razorpay_order_id` | string | Required | `['required', 'string', 'max:100']` |
| `razorpay_payment_id` | string | Required | `['required', 'string', 'max:100']` |
| `razorpay_signature` | string | Required | `['required', 'string', 'size:64']` |

Source: `app/Http/Controllers/Extensions/GatewayController.php:22`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/gateway-payments

Access: Authenticated. Permission: `payments.view`. Rate: 60,1 (requests, minutes).

create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.


Source: `app/Http/Controllers/Extensions/GatewayController.php:51`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/gateway-payments/reconcile

Access: Authenticated. Permission: `payments.manage`. Rate: 10,1 (requests, minutes).

create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `payment_id` | string | Required | `['required', 'string', 'max:100']` |
| `gateway_payment_id` | integer | Required | `['required', 'integer']` |

Source: `app/Http/Controllers/Extensions/GatewayController.php:59`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/orders/{order}/delivery/send-otp

Access: Authenticated. Permission: `delivery.manage`. Rate: 5,1 (requests, minutes).

send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.


Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:27`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/orders/{order}/delivery/verify-otp

Access: Authenticated. Permission: `delivery.manage`. Rate: 10,1 (requests, minutes).

send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `code` | string | Required | `['required', 'digits:6']` |

Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:49`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/orders/{order}/delivery/proof

Access: Authenticated. Permission: `delivery.manage`. Rate: 10,1 (requests, minutes).

send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `proof` | file | Required | `['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']` |
| `recipient_name` | string | Required | `['required', 'string', 'max:120']` |

Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:71`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/orders/{order}/delivery/complete

Access: Authenticated. Permission: `delivery.manage`. Rate: 20,1 (requests, minutes).

send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.


Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:96`. See full reference for conditional branches and example payloads.

### GET /orders/{order}/delivery-proof

Access: Authenticated. Permission: `controller ownership checks`. Rate: 60,1 (requests, minutes).

send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.


Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:115`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/accounting/accounts

Access: Authenticated. Permission: `accounting.view`. Rate: 60,1 (requests, minutes).

accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.


Source: `app/Http/Controllers/Extensions/AccountingController.php:14`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/accounting/accounts

Access: Authenticated. Permission: `accounting.manage`. Rate: 20,1 (requests, minutes).

accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `code` | string | Required | `['required', 'alpha_dash', 'max:30', Rule::unique('growya_journal_accounts', 'code')->where('business_id', $business->id)]` |
| `name` | string | Required | `['required', 'string', 'max:150']` |
| `type` | string | Required | `['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])]` |
| `is_cash` | boolean | Required | `['required', 'boolean']` |

Source: `app/Http/Controllers/Extensions/AccountingController.php:21`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/accounting/journals

Access: Authenticated. Permission: `accounting.view`. Rate: 60,1 (requests, minutes).

accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.


Source: `app/Http/Controllers/Extensions/AccountingController.php:31`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/accounting/journals

Access: Authenticated. Permission: `accounting.manage`. Rate: 20,1 (requests, minutes).

accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `lines` | array | Required | `['required', 'array', 'max:100']` |
| `request_key` | string | Required | `['required', 'string', 'max:100']` |
| `entry_date` | string | Required | `['required', 'date_format:Y-m-d', 'before_or_equal:today']` |
| `memo` | string | Required | `['required', 'string', 'max:500']` |
| `lines.*.account_id` | integer | Required | `['required', 'integer']` |
| `lines.*.debit_paise` | integer | Required | `['required', 'integer', 'min:0', 'max:1000000000000']` |
| `lines.*.credit_paise` | integer | Required | `['required', 'integer', 'min:0', 'max:1000000000000']` |

Source: `app/Http/Controllers/Extensions/AccountingController.php:44`. See full reference for conditional branches and example payloads.

### POST /businesses/{business}/accounting/journals/{journal}/reverse

Access: Authenticated. Permission: `accounting.manage`. Rate: 10,1 (requests, minutes).

accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `request_key` | string | Required | `['required', 'string', 'max:100']` |
| `entry_date` | string | Required | `['required', 'date_format:Y-m-d', 'before_or_equal:today']` |
| `memo` | string | Required | `['required', 'string', 'max:500']` |

Source: `app/Http/Controllers/Extensions/AccountingController.php:57`. See full reference for conditional branches and example payloads.

### GET /businesses/{business}/accounting/reports

Access: Authenticated. Permission: `accounting.view`. Rate: 30,1 (requests, minutes).

accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `from` | string | Optional | `['nullable', 'date_format:Y-m-d']` |
| `to` | string | Optional | `['nullable', 'date_format:Y-m-d', 'after_or_equal:from']` |

Source: `app/Http/Controllers/Extensions/AccountingController.php:69`. See full reference for conditional branches and example payloads.

### GET /admin/tools/{kind}

Access: Authenticated. Permission: `kind-specific platform permission`. Rate: 60,1 (requests, minutes).

kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.


Source: `app/Http/Controllers/Extensions/AdminToolsController.php:31`. See full reference for conditional branches and example payloads.

### POST /admin/tools/{kind}

Access: Authenticated. Permission: `kind-specific platform permission`. Rate: 20,1 (requests, minutes).

kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `status` | string | Required | `['required', Rule::in(['open', 'reviewing', 'resolved', 'dismissed'])]` |
| `group_name` | string | Required | `['required', Rule::in(array_keys(self::SETTINGS))]` |
| `key_name` | string | Required | `['required', 'string', 'max:60']` |
| `value` | string | Optional | `['nullable', 'string', 'max:3000']` |
| `is_public` | boolean | Required | `['required', 'boolean']` |
| `name` | string | Required | `['required', 'string', 'max:200']` |
| `festival_date` | string | Required | `['required', 'date_format:Y-m-d']` |
| `default_caption` | string | Optional | `['nullable', 'string', 'max:2200']` |
| `default_prompt` | string | Optional | `['nullable', 'string', 'max:8000']` |
| `region_filters` | array | Optional | `['nullable', 'array', 'max:50']` |
| `region_filters.*` | string | Optional | `['string', 'max:100']` |
| `placement` | string | Required | `['required', Rule::in(['home', 'marketplace', 'showroom'])]` |
| `city_id` | integer | Optional | `['nullable', 'integer', 'exists:cities,id']` |
| `image` | file | Required | `[$row->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']` |
| `cta_text` | string | Optional | `['nullable', 'string', 'max:100']` |
| `cta_url` | string | Optional | `['nullable', 'url', 'starts_with:https://', 'max:2048']` |
| `starts_at` | date | Optional | `['nullable', 'date']` |
| `ends_at` | date | Optional | `['nullable', 'date', 'after:starts_at']` |
| `sort_order` | integer | Required | `['required', 'integer', 'min:0', 'max:10000']` |

Source: `app/Http/Controllers/Extensions/AdminToolsController.php:38`. See full reference for conditional branches and example payloads.

### PUT /admin/tools/{kind}/{item}

Access: Authenticated. Permission: `kind-specific platform permission`. Rate: 20,1 (requests, minutes).

kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

| Field | Type | Requirement | Exact rules |
|---|---|---|---|
| `status` | string | Required | `['required', Rule::in(['open', 'reviewing', 'resolved', 'dismissed'])]` |
| `group_name` | string | Required | `['required', Rule::in(array_keys(self::SETTINGS))]` |
| `key_name` | string | Required | `['required', 'string', 'max:60']` |
| `value` | string | Optional | `['nullable', 'string', 'max:3000']` |
| `is_public` | boolean | Required | `['required', 'boolean']` |
| `name` | string | Required | `['required', 'string', 'max:200']` |
| `festival_date` | string | Required | `['required', 'date_format:Y-m-d']` |
| `default_caption` | string | Optional | `['nullable', 'string', 'max:2200']` |
| `default_prompt` | string | Optional | `['nullable', 'string', 'max:8000']` |
| `region_filters` | array | Optional | `['nullable', 'array', 'max:50']` |
| `region_filters.*` | string | Optional | `['string', 'max:100']` |
| `placement` | string | Required | `['required', Rule::in(['home', 'marketplace', 'showroom'])]` |
| `city_id` | integer | Optional | `['nullable', 'integer', 'exists:cities,id']` |
| `image` | file | Required | `[$row->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']` |
| `cta_text` | string | Optional | `['nullable', 'string', 'max:100']` |
| `cta_url` | string | Optional | `['nullable', 'url', 'starts_with:https://', 'max:2048']` |
| `starts_at` | date | Optional | `['nullable', 'date']` |
| `ends_at` | date | Optional | `['nullable', 'date', 'after:starts_at']` |
| `sort_order` | integer | Required | `['required', 'integer', 'min:0', 'max:10000']` |

Source: `app/Http/Controllers/Extensions/AdminToolsController.php:38`. See full reference for conditional branches and example payloads.

### DELETE /admin/tools/{kind}/{item}

Access: Authenticated. Permission: `kind-specific platform permission`. Rate: 20,1 (requests, minutes).

kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.


Source: `app/Http/Controllers/Extensions/AdminToolsController.php:84`. See full reference for conditional branches and example payloads.

### POST /webhooks/razorpay

Access: Signed provider webhook (no bearer token). Permission: `controller ownership checks`. Rate: 120,1 (requests, minutes).

create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.


Source: `app/Http/Controllers/Extensions/GatewayController.php:35`. See full reference for conditional branches and example payloads.

## Provider and scope boundaries

External credentials, Meta app permissions, Firebase/mobile SDK setup and real staging acceptance are required. No real SMS/push/generation/post/payment was sent by the development session. Provider contracts were checked against official docs; provider behavior was tested with HTTP fakes.

Supported: text/single-image AI; Facebook text/single image; Instagram single JPEG; daily/weekly recurrence; uniform exclusive GST + flat shipping; captured Razorpay payment verification; OTP/POD; manual double-entry journal and cash movement. Not implemented here: AI video, reels/carousels, Google Business/YouTube/X publishing, automatic Razorpay refund execution, per-item HSN/multi-rate/inclusive tax engine or statutory filing, bank feeds/automatic operational journal posting. A source-backed reference is not proof that all 411 routes were called live.

Official provider references:
- https://firebase.google.com/docs/cloud-messaging/send/v1-api
- https://razorpay.com/docs/payments/server-integration/php/integration-steps/
- https://developers.openai.com/api/docs/guides/image-generation
- https://developers.openai.com/api/docs/guides/text
- https://developers.facebook.com/docs/pages-api/posts/
- https://developers.facebook.com/docs/instagram-platform/instagram-api-with-facebook-login/content-publishing/
- https://www.postman.com/meta/instagram/documentation/6yqw8pt/instagram-api
- https://laravel.com/framework/docs/13.x/queues
