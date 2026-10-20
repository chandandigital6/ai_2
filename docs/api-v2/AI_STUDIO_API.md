# AI Plans & Brand Studio API — Release 3

Base URL: `https://YOUR_DOMAIN/api/v1`. Authentication: Bearer token with `growya` ability. Send `Accept: application/json`. All new APIs use the same business-scoped checks as web. Super admin approval is a separate privileged action.

This file supersedes the AI section in older Release 2 documentation. The existing login and other module APIs keep their existing contract.

## Flutter integration sequence

1. Owner login, fetch `GET businesses/{business}/ai/plans` and display active packages.
2. Owner sends `POST .../ai/plan-requests`, preserving request_key on retries. Show pending state.
3. Super admin reviews from web or privileged API; mobile must never hold the admin token.
4. Refresh plans; read approved `entitlements.packs` and `entitlements.available_credits`.
5. Fetch/save brand profile, multipart-upload logo/reference images and save named presets.
6. POST generation with plan_request_id, same-type preset and/or current prompt. Never send model or credit cost.
7. On 202 show queued/progress UI and poll generation GET every 3.5–5s. Keep polling after navigating back if still running. Stop on completed/failed; show error on failure.
8. completed text → display output_json.text; completed image → GET media path with Authorization to fetch bytes for Image.memory/download. Unauthenticated Image.network will not work.
9. Refresh credits after submission/completion/failure. Do not use aggregate legacy wallet as usable package balance.

## Endpoints

### `GET /businesses/{business}/ai/plans`

**Function:** `app/Http/Controllers/Extensions/AiPlanController.php::catalog()`

{data:{plans:[active plans],entitlements:{available_credits,max_prompts,max_references,packs:[approved unexpired requests]},requests:paginator}}

### `POST /businesses/{business}/ai/plan-requests`

**Function:** `app/Http/Controllers/Extensions/AiPlanController.php::requestPlan()`

201 {data:AiPlanRequest}. Only business owner; one pending request at a time. Same request_key + same payload returns the original request; changed payload is 422. Snapshot price/models/rates/limits are fixed at request time.

**Validation:** ai_plan_id required active plan integer; request_key required <=100; payment_reference optional <=255; note optional <=2000. Active approved business and recorded owner only.

**Example**
```json
{
  "ai_plan_id": "{{ai_plan_id}}",
  "request_key": "{{ai_package_request_key}}",
  "payment_reference": "UPI-reference-optional",
  "note": "Please verify and approve"
}
```

### `GET /admin/ai-plans`

**Function:** `app/Http/Controllers/Extensions/AiPlanController.php::adminIndex()`

200 {data:{plans:[],requests:paginator,editing:null|AiPlan}}. Optional status=pending|approved|rejected, edit=PLAN_ID, page. Super admin only.

### `POST /admin/ai-plans`

**Function:** `app/Http/Controllers/Extensions/AiPlanController.php::store()`

201 {data:AiPlan}; super admin only.

**Validation:** name required <=120; description optional <=2000; price_paise required integer 0..100000000 (or price_rupees decimal string with <=2 fractional digits); credits 1..1000000; valid_days 1..3650; text_model/image_model nullable allowlisted IDs, at least one required; text_credits/image_credits 1..1000000, included type cost <=credits; max_prompts 0..200; max_references 0..10; is_active boolean required. currency is server-owned INR.

**Example**
```json
{
  "name": "Business AI",
  "description": "Jewellery creatives package",
  "price_paise": 99900,
  "credits": 1000,
  "valid_days": 30,
  "text_model": "gpt-4.1-mini",
  "image_model": "gpt-image-1",
  "text_credits": 1,
  "image_credits": 15,
  "max_prompts": 10,
  "max_references": 5,
  "is_active": true
}
```

### `PUT /admin/ai-plans/{plan}`

**Function:** `app/Http/Controllers/Extensions/AiPlanController.php::update()`

200 {data:AiPlan}; affects future requests, never rewrites approved/pending snapshots. Set is_active=false to deactivate rather than deleting.

**Validation:** name required <=120; description optional <=2000; price_paise required integer 0..100000000 (or price_rupees decimal string with <=2 fractional digits); credits 1..1000000; valid_days 1..3650; text_model/image_model nullable allowlisted IDs, at least one required; text_credits/image_credits 1..1000000, included type cost <=credits; max_prompts 0..200; max_references 0..10; is_active boolean required. currency is server-owned INR.

**Example**
```json
{
  "name": "Business AI",
  "description": "Jewellery creatives package",
  "price_paise": 99900,
  "credits": 1000,
  "valid_days": 30,
  "text_model": "gpt-4.1-mini",
  "image_model": "gpt-image-1",
  "text_credits": 1,
  "image_credits": 15,
  "max_prompts": 10,
  "max_references": 5,
  "is_active": true
}
```

### `POST /admin/ai-plan-requests/{purchase}/review`

**Function:** `app/Http/Controllers/Extensions/AiPlanController.php::review()`

200 {data:AiPlanRequest}. decision approved or rejected. Rejection requires review_note. Approval starts validity and grants package credits once. A repeated same decision is idempotent; reversing a review is 422. No automatic payment charge.

**Validation:** decision required approved|rejected; review_note <=2000, required when rejected. Only active super_admin.

**Example**
```json
{
  "decision": "approved",
  "review_note": "Payment verified manually"
}
```

### `GET /businesses/{business}/ai/brand`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::index()`

200 {data:{profile:null|AiBrandProfile,assets:[],presets:[],entitlements:{available_credits,max_prompts,max_references,packs:[]}}}. Private disk paths are hidden. Build asset GET paths from IDs.

### `PUT /businesses/{business}/ai/brand`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::profile()`

200 {data:AiBrandProfile}. ai.settings.manage. No paid package required for profile setup.

**Validation:** All editable fields are a complete profile update: brand_name/tagline/contact/colors optional strings <=255, address <=2000, instructions <=5000; language required <=60; default_size required 1024x1024|1024x1536|1536x1024.

**Example**
```json
{
  "brand_name": "My Jewellers",
  "tagline": "Purity you can trust",
  "contact": "+91 9876543210",
  "address": "Kanpur, Uttar Pradesh",
  "colors": "Maroon and white",
  "language": "Hindi",
  "default_size": "1024x1536",
  "instructions": "Keep jewellery bright and large. Preserve product design and brand logo."
}
```

### `POST /businesses/{business}/ai/assets`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::upload()`

201 {data:AiBrandAsset}. multipart image, kind logo|reference, label. PNG/JPEG/WebP <=5120 KB; dimensions <=8192x8192. Logo replaces old logo and is allowed before a package. References require active allowance. Private disk paths hidden.

**Validation:** kind required logo|reference; label required <=120; image required multipart PNG/JPG/WebP <=5120 KB, dimensions <=8192x8192.

**Example** (multipart)
```json
{
  "kind": "reference",
  "label": "Gold necklace",
  "image": "FILE"
}
```

### `GET /businesses/{business}/ai/assets/{asset}`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::asset()`

200 binary private image (requires Bearer). Never treat this response as JSON. No public URL. Outsider 403, wrong business/removed asset 404.

### `DELETE /businesses/{business}/ai/assets/{asset}`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::deleteAsset()`

200 {data:{deleted:true}}. Soft deletes selection; existing queued input bytes are retained. ai.settings.manage.

### `POST /businesses/{business}/ai/presets`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::storePreset()`

201 {data:AiPromptPreset}. ai.settings.manage and active saved prompt capacity; selected references must be live references from this business within active library allowance.

**Validation:** name required <=120; generation_type required text|image; prompt required 5..8000; negative_prompt optional <=2000; size required supported size; language required <=60; reference_ids optional array <=10 of distinct live business reference IDs (logo excluded).

**Example**
```json
{
  "name": "Daily Jewellery",
  "generation_type": "image",
  "prompt": "Premium jewellery on the right; clean negative space on the left. Preserve the supplied product.",
  "negative_prompt": "No extra jewellery, no wrong logo",
  "reference_ids": [],
  "size": "1024x1536",
  "language": "Hindi"
}
```

### `PUT /businesses/{business}/ai/presets/{preset}`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::updatePreset()`

200 {data:AiPromptPreset}. Full replacement of editable fields; omitted reference_ids can be cleared with []. Cannot edit another business preset.

**Validation:** name required <=120; generation_type required text|image; prompt required 5..8000; negative_prompt optional <=2000; size required supported size; language required <=60; reference_ids optional array <=10 of distinct live business reference IDs (logo excluded).

**Example**
```json
{
  "name": "Daily Jewellery",
  "generation_type": "image",
  "prompt": "Premium jewellery on the right; clean negative space on the left. Preserve the supplied product.",
  "negative_prompt": "No extra jewellery, no wrong logo",
  "reference_ids": [],
  "size": "1024x1536",
  "language": "Hindi"
}
```

### `DELETE /businesses/{business}/ai/presets/{preset}`

**Function:** `app/Http/Controllers/Extensions/AiBrandController.php::deletePreset()`

200 {data:{deleted:true}}. ai.settings.manage. Existing generations keep their prompt snapshot.

### `GET /businesses/{business}/ai`

**Function:** `app/Http/Controllers/Extensions/AiController.php::index()`

200 {data:{wallet:{total_credits,used_credits,available_credits},entitlements:{available_credits,max_prompts,max_references,packs:[]},generations:paginator}}. Usable credits come from entitlements, not legacy wallet. input_json/request_key/private paths are not returned.

### `POST /businesses/{business}/ai`

**Function:** `app/Http/Controllers/Extensions/AiController.php::store()`

202 {data:AiGeneration including id,status,model,credits_used,output_json,error,error_code,media:[]}. Usually queued; poll show. Select approved plan_request_id or server chooses first eligible package expiring soon. Model/cost come only from that package snapshot. Same request_key/payload is once-only debit. New request requires new key. At most two queued/processing jobs per business.

**Validation:** generation_type required text|image; plan_request_id optional approved package ID (recommended explicit); preset_id optional same-business same-type preset; prompt required 5..8000 without preset, optional with preset; request_key required <=100; size/language optional overrides (size allowlist, language <=60); vary_design optional boolean, default true; reference_ids optional array <=10 of distinct business reference IDs. Omit reference_ids to use saved preset references, or all business references for a custom prompt; [] means no references except logo. Selected count <=chosen package max_references, selected images+logo total <=20 MB. Client model,credits_used,business_id are prohibited.

**Example**
```json
{
  "generation_type": "image",
  "plan_request_id": "{{ai_purchase_id}}",
  "preset_id": "{{ai_preset_id}}",
  "prompt": "Create a fresh festive jewellery poster for today",
  "request_key": "{{ai_generation_request_key}}",
  "vary_design": true
}
```

### `GET /businesses/{business}/ai/generations/{generation}`

**Function:** `app/Http/Controllers/Extensions/AiController.php::show()`

200 {data:AiGeneration,media:[{id,media_type,download_path}]}. Poll every 3.5–5 seconds while queued/processing. completed: output_json.text or output_json.image=true with media. failed: error,error_code, credits refunded. Stop polling on completed/failed. input_json and private storage paths are omitted.

### `GET /businesses/{business}/ai/media/{media}`

**Function:** `app/Http/Controllers/Extensions/AiController.php::media()`

200 binary image attachment. Add ?inline=1 for preview. Requires ai.view and Bearer. download_path is relative to /api/v1; do not expose a provider key or public disk path.

### `GET /businesses/{business}/ai/credits`

**Function:** `app/Http/Controllers/Extensions/AiController.php::credits()`

200 {data:{wallet:{total_credits,used_credits,available_credits},entitlements:{available_credits,max_prompts,max_references,packs:[]},transactions:paginator}}. Read usable credits from entitlements. Package available = snapshot.credits - used_credits while unexpired.

### `POST /admin/businesses/{business}/ai/credits`

**Function:** `app/Http/Controllers/Extensions/AiController.php::grant()`

200 {data:{total_credits,used_credits,available_credits}}. Legacy manual wallet grant; requires existing ai.credits.manage platform access. Does not unlock models or fund a selected package; use owner request + super admin approval for this release.

**Validation:** credits integer 1..1000000 and request_key <=100 required. This is not the package approval endpoint.

**Example**
```json
{
  "credits": 100,
  "request_key": "{{ai_grant_request_key}}"
}
```

## Permissions and plan rules

- ai.view: reads/results/private media; ai.use: generation; ai.settings.manage: profile/assets/presets. Staff needs view + use to see results after generating. Owner automatically has own-business access; package requests are owner-only.
- /admin/ai-plans and review APIs require active super_admin, regardless of other global permission grants. Legacy manual-grant API retains its existing permission but cannot create an AI package entitlement.
- Each approved request is a separate credit bucket with its own model/rates/expiry snapshot. Editing a plan does not change existing requests.
- Multiple packages: library allowance uses highest active max_prompts/max_references. Individual generation is limited by selected package reference allowance. Logo is additional.
- Expired package cannot start a new generation. A job reserved before expiry may complete; failed jobs refund to the original package without extending expiry.
- Reference images are sent as actual visual inputs to the provider. Text requests use Responses input_image; image requests with references use image edits. No-reference images use image generations.
- Text allowlist: gpt-4.1-mini, gpt-4.1. Images: gpt-image-1-mini, gpt-image-1, gpt-image-1.5. Configurable in config/growya_ai.php; provider account must have access. No arbitrary client model names.
- Output sizes are 1024x1024, 1024x1536, 1536x1024. Image quality medium, PNG, one image/request. Different design is best effort; no automatic daily scheduler is included.

## Responses and errors

Generation states: queued → processing → completed|failed. Fields: id,business_id,user_id,ai_plan_request_id,generation_type,model,prompt,language,credits_used,status,output_json,error,error_code,media. Timestamp formats follow Laravel serialization (UTC); convert for display. Polling APIs return current state, not token/image streaming.

- 401: login/token needed; 403: permission/tenant/business inactive/unapproved; 404: missing/foreign/removed related record.
- 422: field validation, no approved package, insufficient credits, wrong type, reference/prompt quota, already pending request or idempotency conflict. Display errors[field] and message.
- 429 from app: JSON {message,error_code:"app_rate_limit",retry_after}; honor Retry-After. Native named limit 10/minute/user. No credit is debited when the app rejects before controller execution.
- 503: provider not configured; no generation created.
- Provider failures become failed generation objects with safe error/error_code and automatic once-only credit refund. Codes include provider_quota, provider_rate_limit, provider_auth, model_unavailable, content_rejected, provider_unavailable, provider_request, provider_timeout, reference_missing, empty_output, invalid_output, access_revoked, worker_timeout and generation_failed.
- A provider HTTP 429 is surfaced in polling as failed + provider_rate_limit or provider_quota, separate from app HTTP 429. Do not interpret a generic 429 as low app credits.
- 419 web session expiry; 413 hosting upload limit; network failures: show user-friendly toast inline and retain same request_key on uncertain retries.

## Idempotency

Use a UUID <=100 characters per NEW generation/package request. Retain it with the original payload on retry after network failure. Never create a fresh key automatically merely because a response was lost. Changing payload while reusing a key returns 422. Failed generations keep their key; an intentional retry is a new generation with a new key. Approval and refund are separately idempotent.

## Official provider implementation references

Implementation checked against official documentation on 20 October 2026; real account access is not implied:
- https://developers.openai.com/api/reference/resources/images/methods/edit
- https://developers.openai.com/api/docs/guides/image-generation
- https://developers.openai.com/api/docs/models/gpt-4.1-mini

Provider calls were faked in automated tests. No live paid generation or external billing validation was performed.