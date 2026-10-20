# Growya API Reference for Flutter

Version 2.0 | 20 October 2026 | 397 API v1 route templates

This reference describes the saved Growya Laravel project API contract. Use it with the companion Postman collection and environment. Request samples are illustrative; replace IDs, emails and file paths with staging records before executing them. The API deployment base URL is configured separately.

## Connection and authentication

- Set `base_url` to `https://YOUR_DOMAIN/api/v1` without a trailing slash.
- Send `Accept: application/json` on all API requests. Send `Content-Type: application/json` for JSON writes; let the multipart client set its own boundary for uploads.
- Protected routes use `Authorization: Bearer <token>`. Do not send web CSRF tokens or depend on a web session.
- Login/register return `data.token`, `data.token_type`, `data.expires_in` (2592000 seconds) and `data.user`. Tokens expire after 30 days. There is no refresh-token endpoint in this release.
- Confirmed 2FA users must supply `two_factor_code` at login. Invalid credentials and missing/invalid 2FA code return HTTP 422, not necessarily 401.
- Clear local session and sign in again on an expired/revoked-token 401. A 403 can mean missing business permission, inactive account/member, unapproved business or participant restrictions; do not loop login on every 403.
- Logout revokes only the current token. Password change revokes every token for the account.

## Registration and business onboarding

`account_type` is `personal` or `business` (default personal). It is account intent, not a privileged global role. Business applicants can read `/businesses/create` for master option data, submit `POST /businesses`, then check their business application status. A new business remains pending until platform approval.

The business controller fills India `country_id` from the active master. A valid `state_id` and matching `city_id` must be supplied. `owner_id` is an optional super-admin-only override. Ordinary applicants own their application. Operational products/orders require an active approved business; onboarding access and membership permissions have their own rules.

## Response contracts

The domain API is a JSON transport over existing business controllers. Do not impose one universal `data` parser on every endpoint.

**Domain list:** `{"data":{"business":{"id":1},"products":{"current_page":1,"data":[],"per_page":15,"total":0}}}`. Product rows are at `data.products.data`. Other list names differ by endpoint.

**Native paginated list:** `GET /users` and `/locations/{kind}` return a paginator at `data`, so rows are `data.data`.

**Mutation:** `{"message":"Saved successfully.","location":"https://YOUR_DOMAIN/businesses/1/products"}`. No created resource ID/payload is guaranteed. A POST action whose original method is named `store` returns 201; most other successful domain mutations return 200. Explicit controller JSON/file responses retain their own status and structure.

**Permissions:** `GET /businesses/{business}/my-permissions` returns `{"data":["products.view","orders.view"]}`.

**Chat poll:** `{"messages":[{"id":7,"mine":false,"body":"Available","attachment":null,"time":"20 Oct, 10:30"}]}`. The key is `messages`, not `data.messages`.

**Notification poll:** direct `count`, `latest_id` and `items`. `created_at` there is relative human-readable text, not an ISO timestamp.

**Validation:** `{"message":"The given data was invalid.","errors":{"price":["The price field is required."]}}`. Import failures may include `import_errors`. Preserve per-field errors for the Flutter form.

The payload serializer omits secrets and may include nulls. Decimal money values can be strings. Eloquent dates and raw SQL date projections can use different formats; do not assume every field has an offset. The saved app config defaults to Asia/Kolkata (APP_TIMEZONE); coordinate deployment timezone before schedule/date conversion.

## Status and client behavior

| Status | Meaning and handling |
|---|---|
| 200 | Successful read or domain action; inspect that endpoint's shape |
| 201 | Signup, native user create or mapped POST store success |
| 401 | Missing/expired/revoked token; clear local auth and sign in |
| 403 | Permission, membership, account, business or participant restriction |
| 404 | Unknown/inaccessible ID, wrong tenant object or unknown route |
| 422 | Field/business-rule validation, invalid transition or quota restriction |
| 429 | Rate limit; back off and respect Retry-After when present |
| 500 | Server failure; show retry feedback, retain diagnostic request context without secrets |

Do not automatically retry mutating POST requests as though they were idempotent. There is no general client idempotency-key contract. Fetch paginated next pages with `page`; do not invent a supported `per_page` filter where the controller does not read it. Fixed page sizes differ across modules.

## Permissions and business selection

1. Log in and load `/businesses`.
2. Keep the selected `business.id` in app state, separately from user ID.
3. Fetch `/businesses/{business}/my-permissions` after selection.
4. Gate screens with view permissions and actions with create/update/manage/delete permissions. Recheck after a 403 or an access-change refresh; the backend checks memberships live.
5. Keep a read-only UI distinct from edit UI. A manager label does not grant automatic rights.

An active super admin has platform scope. A recorded owner has access to their own active business. Staff/manager access is business-local and requires an active membership plus the specific grant. Generic business overview access exists for active members; individual modules remain separately permissioned. Some finance/billing/CRM/chat operations also check owner, assignee or participant context. The permission printed on each endpoint is the module gate, not a replacement for those checks.

GET staff returns assignable permission records. Submit their integer IDs in `permissions`; the effective permission-name list is for Flutter UI decisions. Deleting a staff member removes the business membership, not the user's entire account.

## Files and protected URLs

- File rules are per endpoint; limits such as `max:5120` mean KB for uploaded files.
- Common fields: product/service `featured_image`, business `logo`/`cover`, product CSV `file`, chat `attachment`, payment `proof`, support `attachments[]`.
- Form arrays use bracket paths such as `permissions[0]` or `specifications[0][name]`. For JSON requests use native arrays/objects.
- For file updates on PHP endpoints, use multipart POST with `_method=PUT` or `_method=PATCH` as appropriate, and verify against staging. Ordinary JSON update requests use the route's declared PUT/PATCH verb.
- Download endpoints return actual bytes. Inspect Content-Type and Content-Disposition before opening/saving; do not JSON-decode a PDF/image/CSV response.
- Mutation `location`, chat attachment URLs and notification `open_url` can be original web URLs. Do not follow them with bearer auth expecting a web session. Call the listed API counterpart using the known object ID. Example protected chat file: `/messages/{thread}/files/{message}` under the API base.
- Product CSV import row errors are in the import response. The old session-bound error-report download cannot recover the prior stateless API request.

## Core request samples

Samples use example IDs only. Existing record IDs, available plans, product stock, account role and business approval must agree with the target database.

### POST /auth/login

```json
{
  "email": "{{test_email}}",
  "password": "{{test_password}}",
  "device_name": "Flutter Android"
}
```

### POST /auth/register

```json
{
  "name": "Demo User",
  "email": "{{new_user_email}}",
  "phone": "9876543210",
  "password": "StrongPassword123!",
  "password_confirmation": "StrongPassword123!",
  "account_type": "business",
  "device_name": "Flutter Android"
}
```

### POST /businesses

```json
{
  "name": "Demo Jewellers",
  "state_id": 1,
  "city_id": 1,
  "email": "shop@example.com",
  "phone": "9876543210",
  "address": "Demo street address",
  "pincode": "208001",
  "cod_enabled": true,
  "upi_enabled": false
}
```

### POST /businesses/{business}/staff

```json
{
  "email": "{{staff_email}}",
  "name": "Demo Staff",
  "phone": "9876543211",
  "password": "StrongPassword123!",
  "password_confirmation": "StrongPassword123!",
  "role": "staff",
  "permissions": [
    1
  ]
}
```

### POST /businesses/{business}/catalog/{kind}

```json
{
  "name": "Gold Rings",
  "category_type": "product",
  "is_active": true,
  "sort_order": 0
}
```

### POST /businesses/{business}/products

```json
{
  "name": "Gold Ring",
  "category_id": 1,
  "sku": "DEMO-RING-001",
  "price": 25000,
  "offer_price": 24000,
  "stock_status": "in_stock",
  "stock_quantity": 5,
  "status": "active",
  "is_featured": false,
  "is_trending": false,
  "specifications": [
    {
      "name": "Purity",
      "value": "22KT"
    }
  ]
}
```

### POST /businesses/{business}/services

```json
{
  "name": "Ring resizing",
  "price_type": "fixed",
  "price": 500,
  "duration_minutes": 30,
  "status": "active"
}
```

### POST /shops/{business}/cart

```json
{
  "product_id": 1,
  "quantity": 1
}
```

### POST /shops/{business}/checkout

```json
{
  "customer_name": "Demo Customer",
  "customer_phone": "9876543210",
  "address": "Demo street address",
  "city": "Kanpur",
  "state": "Uttar Pradesh",
  "pincode": "208001",
  "payment_method": "cod",
  "save_address": false
}
```

### POST /businesses/{business}/messages/start

```json
{
  "context_type": "general"
}
```

### POST /messages/{thread}

```json
{
  "body": "Is this product available?"
}
```

### POST /businesses/{business}/subscriptions

```json
{
  "plan_id": 1,
  "billing_cycle": "monthly"
}
```

For `catalog/attributes`, send `{"name":"Purity"}` instead of the category example. Then POST `{"value":"22KT","sort_order":0}` to `/businesses/{business}/catalog-attributes/{attribute}/values`. `permissions` in the staff example must be replaced with actual assignable IDs from the staff API.

## Integration order

1. Auth, account profile and secure token storage.
2. Business selection, onboarding/master options and effective permissions.
3. Product/service reads, categories/attributes, then create/update/file uploads.
4. Customer addresses/cart/checkout and order history.
5. Seller orders, manual proof review, returns/delivery and CRM.
6. Chat polling, in-app notifications, support and document downloads.
7. Plans/subscription requests and permission-specific analytics/finance.

## Scope and handoff

Version 2 adds provider-gated mobile OTP, FCM lifecycle, text/image AI and credit wallet, Facebook/Instagram OAuth and publishing, recurring schedules, festival administration, activity/history/share links, daily analytics, photo reviews, admin tools, shipping/GST, Razorpay verification, delivery OTP/POD and balanced accounting journals. Providers remain disabled until configured. Read ../../PENDING_MODULES_SETUP_HINDI.md and ../NEW_MODULES_API.md for setup and supported boundaries. Refund execution is manual; this release does not automate gateway refunds, multi-rate GST/HSN compliance, AI video, or publishing to Google Business/YouTube/X. Starter-kit personal Teams/appearance/2FA-setup Livewire UI is not exposed as a complete separate JSON account-UI API.

See ../VALIDATION.md for current test results and ../validation-results.json for machine-readable evidence. This documentation is source-backed contract extraction, not a fresh test of all endpoints against a live host. Use staging role accounts and records for integration acceptance. The legacy authenticated `GET /api/user` remains outside v1; new clients should use `/auth/me`.

## How to use the full endpoint reference

Each entry lists the method/path, auth audience, content type, module permission, path parameters, validated fields, any direct query/read inputs, response keys or explicit JSON source, conditional workflow notes and source locations. Exact Laravel validation blocks are retained for complex conditional fields. Fields marked Conditional/Categories only/Create only must be interpreted in that context. Example bodies are starting points, not proof that arbitrary sample IDs exist.
