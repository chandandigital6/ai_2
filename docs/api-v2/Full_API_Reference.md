> Release 3 AI update: use [AI_STUDIO_API.md](AI_STUDIO_API.md) for current AI models, packages, credits, presets and responses. Older AI sections below are superseded.

# Growya API Reference for Flutter

Version 3.0 | 20 October 2026 | 411 API v1 route templates

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

# Full endpoint catalog

## POST /auth/login

Login - Auth

- Group: 01 Accounts and access
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AuthController.php:30`
- Rate limits: 5 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| email | string | Required | required; email |
| password | string | Required | required; string |
| device_name | string | Required | required; string; max:100 |
| two_factor_code | string | Optional | nullable; string; max:20 |

Example payload (IDs must be replaced):
```json
{
  "email": "{{test_email}}",
  "password": "{{test_password}}",
  "device_name": "Flutter Android"
}
```

- Wrong credentials/inactive account and missing/invalid confirmed 2FA code return validation HTTP 422. Do not treat every login failure as HTTP 401.

Exact explicit JSON expression:
```php
json(['data' => $this->issue($user, $data['device_name'])])
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AuthController.php:31`
```php
['email' => ['required', 'email'], 'password' => ['required', 'string'], 'device_name' => ['required', 'string', 'max:100'], 'two_factor_code' => ['nullable', 'string', 'max:20']]
```

## POST /auth/logout

Logout - Auth

- Group: 01 Accounts and access
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AuthController.php:52`

Exact explicit JSON expression:
```php
json(['message' => 'Current token revoked.'])
```

## GET /auth/me

Me - Auth

- Group: 01 Accounts and access
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AuthController.php:47`

Exact explicit JSON expression:
```php
json(['data' => $request->user()->only(['id', 'name', 'email', 'phone', 'user_type', 'status'])])
```

## PUT /auth/password

Password - Account

- Group: 01 Accounts and access
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:77`
- Rate limits: 5 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| current_password | string | Required | required; string |
| password | string | Required | required; string; min:12; confirmed |
| password_confirmation | string | Required when password is supplied | same:password; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
```json
{
  "current_password": "{{test_password}}",
  "password": "StrongPassword123!",
  "password_confirmation": "StrongPassword123!"
}
```

- All API tokens for this account are revoked after the password changes. Clear local credentials and sign in again.

Exact explicit JSON expression:
```php
json(['message' => 'Password updated. All API tokens revoked; sign in again.'])
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AccountController.php:78`
```php
['current_password' => ['required', 'string'], 'password' => ['required', 'string', 'min:12', 'confirmed']]
```

## PATCH /auth/profile

Profile - Account

- Group: 01 Accounts and access
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:62`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | When supplied | sometimes; required; string; max:255 |
| email | string | When supplied | sometimes; required; email; Unique within the server rule scope |
| phone | string | Optional | sometimes; nullable; regex:/^[6-9][0-9]{9}$/; Unique within the server rule scope |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo User",
  "email": "{{test_email}}",
  "phone": "9876543210"
}
```

Exact explicit JSON expression:
```php
json(['data' => $user->only('id', 'name', 'email', 'phone', 'user_type', 'status')])
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AccountController.php:64`
```php
['name' => ['sometimes', 'required', 'string', 'max:255'], 'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['sometimes', 'nullable', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)]]
```

## POST /auth/register

Register - Auth

- Group: 01 Accounts and access
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AuthController.php:22`
- Rate limits: 5 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| device_name | string | Optional | nullable; string; max:100 |
| password | string | Required | required; string; confirmed; deployment Password::default() rules |
| account_type | string | Optional | sometimes; Allowed: personal, business |
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/; Unique within the server rule scope |
| referral_code | string | Optional | nullable; string; max:20; Existing allowed record; referential scope is checked server side |
| name | string | Required | required; string; max:255 |
| email | string | Required | required; string; max:255; email; unique:users,email |
| password_confirmation | string | Required | same:password |

Example payload (IDs must be replaced):
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

- account_type personal/business expresses intent. Business registration does not grant a global privileged role or bypass business approval. Password rules depend on deployment Password::default(); use a strong confirmed password.

Exact explicit JSON expression:
```php
json(['data' => $this->issue($user, $request->input('device_name', 'mobile')), 'next' => $user->user_type === 'owner' ? '/api/v1/businesses/create' : '/api/v1/dashboard'], 201)
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AuthController.php:23`
```php
['device_name' => ['nullable', 'string', 'max:100']]
```

`app/Actions/Fortify/CreateNewUser.php:32`
```php
[
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['sometimes', Rule::in(['personal', 'business'])],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')],
            'referral_code' => ['nullable', 'string', 'max:20', Rule::exists('growya_referral_codes', 'code')],
        ]
```

`app/Concerns/ProfileValidationRules.php:17`
```php
[
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ]
```

## GET /businesses/{business}/my-permissions

My permissions - Inline permissions

- Group: 01 Accounts and access
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: business.view
- Source: `routes/api.php:30`
- Path inputs: `business`

Domain response data keys: `data.data`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /locations/{kind}

Locations - Account

- Group: 01 Accounts and access
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:90`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `kind`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| country_id | integer | Optional | nullable; integer; min:1 |
| state_id | integer | Optional | nullable; integer; min:1 |
| search | string | Optional | nullable; string; max:100 |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

- kind is countries, states or cities. Optional country_id applies to states, state_id applies to cities; page size is 100.

Exact explicit JSON expression:
```php
json(['data' => $query->orderBy('name')->paginate(100)])
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AccountController.php:91`
```php
['country_id' => ['nullable', 'integer', 'min:1'], 'state_id' => ['nullable', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:100']]
```

## GET /users

Users - Account

- Group: 01 Accounts and access
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:23`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| search | string | Optional | nullable; string; max:100 |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact explicit JSON expression:
```php
json(['data' => User::select('id', 'name', 'email', 'phone', 'user_type', 'status')->when($data['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))->latest('id')->paginate(20)])
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AccountController.php:25`
```php
['search' => ['nullable', 'string', 'max:100']]
```

## POST /users

Create user - Account

- Group: 01 Accounts and access
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:31`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| email | string | Required | required; email; Unique within the server rule scope |
| phone | string | Optional | nullable; regex:/^[6-9][0-9]{9}$/; Unique within the server rule scope |
| password | string | Required | required; string; min:12; confirmed |
| password_confirmation | string | Required when password is supplied | same:password; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "email": "{{test_email}}",
  "password": "StrongPassword123!",
  "password_confirmation": "StrongPassword123!"
}
```

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact explicit JSON expression:
```php
json(['data' => $user->only('id', 'name', 'email', 'phone', 'status')], 201)
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AccountController.php:33`
```php
['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('users', 'email')], 'phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')], 'password' => ['required', 'string', 'min:12', 'confirmed']]
```

## DELETE /users/{user}

Delete user - Account

- Group: 01 Accounts and access
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:50`
- Path inputs: `user`

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- DELETE deactivates the account and revokes its tokens. It preserves related history rather than physically deleting the account.

Exact explicit JSON expression:
```php
json(['message' => 'Account deactivated; history preserved.'])
```

## PATCH /users/{user}

Update user - Account

- Group: 01 Accounts and access
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Api/V1/AccountController.php:40`
- Path inputs: `user`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | When supplied | sometimes; required; string; max:255 |
| email | string | When supplied | sometimes; required; email; Unique within the server rule scope |
| status | string | Optional | sometimes; Allowed: active, inactive |

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact explicit JSON expression:
```php
json(['data' => $user->only('id', 'name', 'email', 'phone', 'status')])
```

Exact validation blocks:

`app/Http/Controllers/Api/V1/AccountController.php:43`
```php
['name' => ['sometimes', 'required', 'string', 'max:255'], 'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'status' => ['sometimes', Rule::in(['active', 'inactive'])]]
```

## GET /

List - Public Home

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicHomeController.php:13`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.categories`, `data.featuredBusinesses`, `data.featuredProducts`, `data.popularServices`, `data.stats`, `data.paidPromotions`, `data.paidProductPromotions`.

## GET /b/{business:slug}/offers

List - Public Offer

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicOfferController.php:9`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business:slug`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.offers`.

## GET /b/{slug}

Home - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:54`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`

Domain response data keys: `data.business`, `data.products`, `data.services`, `data.gallery`, `data.todayHour`, `data.isOpen`.

## GET /b/{slug}/contact

Contact - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:185`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`

Domain response data keys: `data.business`.

## GET /b/{slug}/gallery

Gallery - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:170`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.gallery`.

## GET /b/{slug}/products

Products - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:93`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.products`.

## GET /b/{slug}/products/{productSlug}

Product - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:122`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`, `productSlug`

Domain response data keys: `data.business`, `data.product`.

## GET /b/{slug}/services

Services - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:141`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.services`.

## GET /b/{slug}/services/{serviceSlug}

Service - Public Business

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicBusinessController.php:155`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`, `serviceSlug`

Domain response data keys: `data.business`, `data.service`.

## GET /businesses/{business}/map-location

Read edit form options - Nearby

- Group: 02 Public discovery and showroom
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: business.update
- Source: `app/Http/Controllers/GrowyaNearbyController.php:96`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/map-location

Update - Nearby

- Group: 02 Public discovery and showroom
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: business.update
- Source: `app/Http/Controllers/GrowyaNearbyController.php:102`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| latitude | number | Required | required; numeric; between:-90,90 |
| longitude | number | Required | required; numeric; between:-180,180 |

Example payload (IDs must be replaced):
```json
{
  "latitude": 100,
  "longitude": 100
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaNearbyController.php:104`
```php
[
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]
```

## GET /marketplace

List - Public Marketplace

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicMarketplaceController.php:13`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| q | string | Optional | nullable; string; max:120 |
| category | string | Optional | nullable; string; max:160 |
| city | string | Optional | nullable; string; max:120 |
| sort | string | Optional | nullable; Allowed: newest, popular, name |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.businesses`, `data.categories`.

Exact validation blocks:

`app/Http/Controllers/PublicMarketplaceController.php:14`
```php
[
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['newest', 'popular', 'name'])],
        ]
```

## GET /nearby-businesses

List - Nearby

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaNearbyController.php:16`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| q | string | Optional | nullable; string; max:120 |
| category | string | Optional | nullable; string; max:160 |
| state_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| city_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| lat | number | Conditional | nullable; required_with:lng; numeric; between:-90,90 |
| lng | number | Conditional | nullable; required_with:lat; numeric; between:-180,180 |
| radius | string | Optional | nullable; Allowed: 5, 10, 25, 50 |
| sort | string | Optional | nullable; Allowed: nearest, newest, popular |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.businesses`, `data.categories`, `data.states`, `data.cities`, `data.geo`.

Exact validation blocks:

`app/Http/Controllers/GrowyaNearbyController.php:17`
```php
[
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:160'],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where('is_active', 1)],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', 1)],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', Rule::in(['5', '10', '25', '50'])],
            'sort' => ['nullable', Rule::in(['nearest', 'newest', 'popular'])],
        ]
```

## GET /offers

List - Public Offers Explore

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicOffersExploreController.php:11`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| q | string | Optional | nullable; string; max:120 |
| type | string | Optional | nullable; Allowed: percentage, fixed, announcement |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.offers`.

Exact validation blocks:

`app/Http/Controllers/PublicOffersExploreController.php:12`
```php
[
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['percentage', 'fixed', 'announcement'])],
        ]
```

## GET /p/{business}/{product}

Read detail - Public Product

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicProductController.php:10`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`.

## GET /s/{business}/{service}/enquiry

Read create form options - Public Service Enquiry

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicServiceEnquiryController.php:33`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

Domain response data keys: `data.business`, `data.service`.

## POST /s/{business}/{service}/enquiry

Create or save - Public Service Enquiry

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicServiceEnquiryController.php:45`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| customer_name | string | Required | required; string; max:255 |
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |
| preferred_date | string | Optional | nullable; date_format:Y-m-d; after_or_equal:today |
| preferred_time | string | Optional | nullable; date_format:H:i |
| message | string | Optional | nullable; string; max:3000 |
| website | string | Optional | nullable; string; max:0 |

Example payload (IDs must be replaced):
```json
{
  "customer_name": "Demo Customer",
  "phone": "9876543210"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/PublicServiceEnquiryController.php:48`
```php
[
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],

            'preferred_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'preferred_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'message' => [
                'nullable',
                'string',
                'max:3000',
            ],

            // Hidden honeypot field.
            'website' => [
                'nullable',
                'string',
                'max:0',
            ],
        ]
```

## GET /search

List - Public Global Search

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicGlobalSearchController.php:27`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| q | string | Optional | nullable; string; max:120 |
| type | string | Optional | nullable; Allowed: businesses, products, services |
| category_id | integer | Optional | nullable; integer; min:1 |
| city_id | integer | Optional | nullable; integer; min:1 |
| min_price | number | Optional | nullable; numeric; min:0; max:999999999999 |
| max_price | number | Optional | nullable; numeric; min:0; max:999999999999; gte:min_price |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.results`, `data.type`, `data.q`, `data.categories`, `data.cities`, `data.categoryId`, `data.cityId`, `data.min`, `data.max`.

Exact validation blocks:

`app/Http/Controllers/PublicGlobalSearchController.php:28`
```php
[
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['businesses', 'products', 'services'])],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'city_id' => ['nullable', 'integer', 'min:1'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999', 'gte:min_price'],
        ]
```

## GET /search/suggestions

Suggest - Public Global Search

- Group: 02 Public discovery and showroom
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PublicGlobalSearchController.php:93`
- Rate limits: 120 requests / 1 minute(s), 30 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| q | string | Required | required; string; min:2; max:80 |

Exact explicit JSON expression:
```php
json(['items' => []])
json(['items' => $items])
```

Exact validation blocks:

`app/Http/Controllers/PublicGlobalSearchController.php:94`
```php
['q' => ['required', 'string', 'min:2', 'max:80']]
```

## GET /admin/business-claims

Admin - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:105`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; Allowed: pending, under_review, approved, rejected, all |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.claims`, `data.status`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessClaimController.php:107`
```php
['status' => ['nullable', Rule::in(['pending', 'under_review', 'approved', 'rejected', 'all'])]]
```

## PATCH /admin/business-claims/{claim}/review

Review - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:130`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `claim`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; Allowed: under_review, approved, rejected |
| admin_note | string | Required | required; string; min:10; max:2000 |
| expected_owner_id | integer | Required | required; integer; min:1 |
| confirmation | string | Optional | nullable; string |

Example payload (IDs must be replaced):
```json
{
  "decision": "under_review",
  "admin_note": "demo",
  "expected_owner_id": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessClaimController.php:132`
```php
[
            'decision' => ['required', Rule::in(['under_review', 'approved', 'rejected'])],
            'admin_note' => ['required', 'string', 'min:10', 'max:2000'],
            'expected_owner_id' => ['required', 'integer', 'min:1'],
            'confirmation' => ['nullable', 'string'],
        ]
```

## GET /admin/business-ownership

List - Business Ownership

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessOwnershipController.php:20`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| search | string | Optional | nullable; string; max:150 |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.businesses`, `data.search`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessOwnershipController.php:22`
```php
['search' => ['nullable', 'string', 'max:150']]
```

## PATCH /admin/business-ownership/{business}

Update - Business Ownership

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessOwnershipController.php:47`
- Rate limits: 120 requests / 1 minute(s), 10 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| new_owner_id | integer | Required | required; integer; exists:users,id |
| expected_owner_id | integer | Required | required; integer; min:1 |
| confirmation | string | Required | required; string |
| reason | string | Required | required; string; min:10; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "new_owner_id": 1,
  "expected_owner_id": 1,
  "confirmation": "demo",
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessOwnershipController.php:49`
```php
[
            'new_owner_id' => ['required', 'integer', 'exists:users,id'],
            'expected_owner_id' => ['required', 'integer', 'min:1'],
            'confirmation' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]
```

## GET /admin/business-ownership/{business}/edit

Read edit form options - Business Ownership

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessOwnershipController.php:34`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.users`, `data.history`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## GET /admin/business-verifications

List - Business Verification

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessVerificationController.php:23`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; Allowed: pending, approved, rejected, all |
| search | string | Optional | nullable; string; max:150 |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.businesses`, `data.counts`, `data.total`, `data.filter`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/BusinessVerificationController.php:26`
```php
[
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'approved',
                    'rejected',
                    'all',
                ]),
            ],
            'search' => ['nullable', 'string', 'max:150'],
        ]
```

## GET /admin/business-verifications/{business}

Read detail - Business Verification

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessVerificationController.php:79`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/business-verifications/{business}/approve

Approve - Business Verification

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessVerificationController.php:98`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /admin/business-verifications/{business}/pending

Pending - Business Verification

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessVerificationController.php:175`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /admin/business-verifications/{business}/reject

Reject - Business Verification

- Group: 03 Business onboarding and ownership
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessVerificationController.php:133`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| reason | string | Required | required; string; min:5; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessVerificationController.php:136`
```php
[
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]
```

## GET /business-applications

List - Business Request

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessRequestController.php:6`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.businesses`.

## GET /business-claims/{claim}

Read detail - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:113`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `claim`

Domain response data keys: `data.claim`.

## GET /business-claims/{claim}/documents/{document}

Document - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:123`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `claim`, `document`

Response: binary/stream download.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses

List - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:22`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.businesses`.

## POST /businesses

Create or save - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:64`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| owner_id | id | Optional super admin only | required; Existing allowed record; referential scope is checked server side; Conditional branch; see workflow notes and exact rule |
| name | string | Required | required; string; max:255 |
| description | string | Optional | nullable; string; max:10000 |
| category_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| email | string | Optional | nullable; email; max:255 |
| phone | string | Optional | nullable; string; max:20 |
| whatsapp | string | Optional | nullable; string; max:20 |
| address | string | Optional | nullable; string; max:2000 |
| pincode | string | Optional | nullable; string; max:12 |
| country_id | id | Server filled India | required; Existing allowed record; referential scope is checked server side; Conditional branch; see workflow notes and exact rule |
| state_id | id | Required | required; Existing allowed record; referential scope is checked server side |
| city_id | id | Required | required; Existing allowed record; referential scope is checked server side |
| gst_number | string | Optional | nullable; string; max:30 |
| upi_id | id | Optional | nullable; string; max:255 |
| logo | file | Optional | nullable; File size <= 2mb |
| cover | file | Optional | nullable; File size <= 5mb |
| cod_enabled | boolean | Optional | sometimes; boolean |
| upi_enabled | boolean | Optional | sometimes; boolean |

Example payload (IDs must be replaced):
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

Response: `message` and web `location`; no guaranteed resource object.

- country_id is filled by the backend from the active India master (iso_code IND). Supply a state_id from that country and a city_id from that state.

- owner_id is considered only when a super admin explicitly submits it. Normal registration/applicants use their own authenticated account.

- Business creation is an application with pending verification, not automatic public approval.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessController.php:76`
```php
[
                'owner_id' => [
                    'required',
                    Rule::exists('users', 'id')
                        ->where('status', 'active'),
                ],
            ]
```

`app/Http/Controllers/BusinessController.php:241`
```php
[
            'name' => ['required', 'string', 'max:255'],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->whereNull('business_id')->where('category_type', 'business')
                    ->where('is_active', true),
            ],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'pincode' => ['nullable', 'string', 'max:12'],

            'country_id' => [
                'required',
                Rule::exists('countries', 'id')
                    ->where('is_active', true),
            ],

            'state_id' => [
                'required',
                Rule::exists('states', 'id')
                    ->where('country_id', $india->id)
                    ->where('is_active', true),
            ],

            'city_id' => [
                'required',
                Rule::exists('cities', 'id')
                    ->where('is_active', true),
            ],

            'gst_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'upi_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'logo' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('2mb'),
            ],

            'cover' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('5mb'),
            ],

            'cod_enabled' => ['sometimes', 'boolean'],
            'upi_enabled' => ['sometimes', 'boolean'],
        ]
```

## GET /businesses/create

Read create form options - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:57`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.categories`, `data.countries`, `data.states`, `data.cities`, `data.owners`.

## DELETE /businesses/{business}

Delete or remove - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:223`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}

Read detail - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:147`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`.

## PUT /businesses/{business}

Update - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:170`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| description | string | Optional | nullable; string; max:10000 |
| category_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| email | string | Optional | nullable; email; max:255 |
| phone | string | Optional | nullable; string; max:20 |
| whatsapp | string | Optional | nullable; string; max:20 |
| address | string | Optional | nullable; string; max:2000 |
| pincode | string | Optional | nullable; string; max:12 |
| country_id | id | Server filled India | required; Existing allowed record; referential scope is checked server side; Conditional branch; see workflow notes and exact rule |
| state_id | id | Required | required; Existing allowed record; referential scope is checked server side |
| city_id | id | Required | required; Existing allowed record; referential scope is checked server side |
| gst_number | string | Optional | nullable; string; max:30 |
| upi_id | id | Optional | nullable; string; max:255 |
| logo | file | Optional | nullable; File size <= 2mb |
| cover | file | Optional | nullable; File size <= 5mb |
| cod_enabled | boolean | Optional | sometimes; boolean |
| upi_enabled | boolean | Optional | sometimes; boolean |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "state_id": 1,
  "city_id": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- country_id is filled by the backend from the active India master (iso_code IND). Supply a state_id from that country and a city_id from that state.

- owner_id is considered only when a super admin explicitly submits it. Normal registration/applicants use their own authenticated account.

- Business creation is an application with pending verification, not automatic public approval.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessController.php:241`
```php
[
            'name' => ['required', 'string', 'max:255'],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->whereNull('business_id')->where('category_type', 'business')
                    ->where('is_active', true),
            ],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'pincode' => ['nullable', 'string', 'max:12'],

            'country_id' => [
                'required',
                Rule::exists('countries', 'id')
                    ->where('is_active', true),
            ],

            'state_id' => [
                'required',
                Rule::exists('states', 'id')
                    ->where('country_id', $india->id)
                    ->where('is_active', true),
            ],

            'city_id' => [
                'required',
                Rule::exists('cities', 'id')
                    ->where('is_active', true),
            ],

            'gst_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'upi_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'logo' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('2mb'),
            ],

            'cover' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('5mb'),
            ],

            'cod_enabled' => ['sometimes', 'boolean'],
            'upi_enabled' => ['sometimes', 'boolean'],
        ]
```

## GET /businesses/{business}/claim

Read create form options - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:33`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.latest`.

## POST /businesses/{business}/claim

Create or save - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:41`
- Rate limits: 120 requests / 1 minute(s), 3 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| claimed_role | string | Required | required; Allowed: proprietor, partner, director, authorized_representative |
| reason | string | Required | required; string; min:20; max:3000 |
| document_type | string | Required | required; Allowed: gst, business_registration, authorization_letter, other |
| document | file | Required | required; file; mimes:pdf,jpg,jpeg,png,webp; max:5120 |
| declaration | string | Optional | accepted |

Example payload (IDs must be replaced):
```json
{
  "claimed_role": "proprietor",
  "reason": "demo",
  "document_type": "gst"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessClaimController.php:43`
```php
[
            'claimed_role' => ['required', Rule::in(['proprietor', 'partner', 'director', 'authorized_representative'])],
            'reason' => ['required', 'string', 'min:20', 'max:3000'],
            'document_type' => ['required', Rule::in(['gst', 'business_registration', 'authorization_letter', 'other'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'declaration' => ['accepted'],
        ]
```

## GET /businesses/{business}/edit

Read edit form options - Business

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessController.php:163`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.categories`, `data.countries`, `data.states`, `data.cities`, `data.owners`, `data.business`.

## GET /my-business-claims

Read own records - Business Claim

- Group: 03 Business onboarding and ownership
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessClaimController.php:99`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.claims`.

## GET /admin/access-control

List - Role Matrix

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaRoleMatrixController.php:18`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.roles`, `data.permissions`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/access-control/roles/{role}

Update - Role Matrix

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaRoleMatrixController.php:26`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `role`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| permissions | array | Optional | sometimes; array |
| permissions.* | integer | Optional | integer; Existing allowed record; referential scope is checked server side |

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaRoleMatrixController.php:30`
```php
[
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions','id')->where('guard_name', 'web')],
        ]
```

## GET /admin/user-roles

List - User Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaUserRoleController.php:19`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.users`, `data.roles`, `data.search`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/user-roles/{user}

Update - User Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaUserRoleController.php:40`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `user`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| role_id | integer | Required | required; integer; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "role_id": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaUserRoleController.php:47`
```php
[
            'role_id' => [
                'required', 'integer',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->where('guard_name', 'web')->where('name', '!=', 'super_admin');
                }),
            ],
        ]
```

## GET /businesses/{business}/staff

List - Business Staff

- Group: 04 Staff roles and permissions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: staff.view
- Source: `app/Http/Controllers/BusinessStaffController.php:75`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.staff`, `data.roles`, `data.permissions`, `data.canManage`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Staff role is business-local. Permission IDs come from data.permissions in the staff list/options response; do not assume IDs are stable across databases.

- Adding an existing email attaches its account without replacing credentials. For a new email, name, phone and a confirmed password of at least 12 characters are required.

- Owner membership is protected. A delegated manager cannot modify self, grant permissions outside their own grant set, or modify a more privileged member. The no-subscription fallback permits one active non-owner staff member.

## POST /businesses/{business}/staff

Create or save - Business Staff

- Group: 04 Staff roles and permissions
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: staff.manage
- Source: `app/Http/Controllers/BusinessStaffController.php:86`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| email | string | Required | required; email; max:255 |
| name | string | Optional | nullable; string; max:255 |
| phone | string | Optional | nullable; regex:/^[6-9][0-9]{9}$/ |
| password | string | Optional | nullable; string; min:12; confirmed |
| role | string | Required | required; Allowed: admin, manager, staff, marketing, sales |
| permissions | array | Optional | sometimes; array |
| permissions.* | integer | Optional | integer; distinct |
| password_confirmation | string | Required when password is supplied | same:password; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
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

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Staff role is business-local. Permission IDs come from data.permissions in the staff list/options response; do not assume IDs are stable across databases.

- Adding an existing email attaches its account without replacing credentials. For a new email, name, phone and a confirmed password of at least 12 characters are required.

- Owner membership is protected. A delegated manager cannot modify self, grant permissions outside their own grant set, or modify a more privileged member. The no-subscription fallback permits one active non-owner staff member.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessStaffController.php:88`
```php
[
            'email' => ['required', 'email', 'max:255'], 'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(array_keys($this->roles()))],
            'permissions' => ['sometimes', 'array'], 'permissions.*' => ['integer', 'distinct'],
        ]
```

`app/Http/Controllers/BusinessStaffController.php:100`
```php
[
                    'name' => ['required', 'string', 'max:255'], 'phone' => ['required', Rule::unique('users', 'phone')],
                    'password' => ['required', 'string', 'min:12'],
                ]
```

## GET /businesses/{business}/staff/history

Read history - Business Staff

- Group: 04 Staff roles and permissions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: staff.view
- Source: `app/Http/Controllers/BusinessStaffController.php:159`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.audits`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## DELETE /businesses/{business}/staff/{member}

Delete or remove - Business Staff

- Group: 04 Staff roles and permissions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: staff.manage
- Source: `app/Http/Controllers/BusinessStaffController.php:144`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `member`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Staff role is business-local. Permission IDs come from data.permissions in the staff list/options response; do not assume IDs are stable across databases.

- Adding an existing email attaches its account without replacing credentials. For a new email, name, phone and a confirmed password of at least 12 characters are required.

- Owner membership is protected. A delegated manager cannot modify self, grant permissions outside their own grant set, or modify a more privileged member. The no-subscription fallback permits one active non-owner staff member.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /businesses/{business}/staff/{member}

Update - Business Staff

- Group: 04 Staff roles and permissions
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: staff.manage
- Source: `app/Http/Controllers/BusinessStaffController.php:123`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `member`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| role | string | Required | required; Allowed: admin, manager, staff, marketing, sales |
| is_active | boolean | Required | required; boolean |
| permissions | array | Optional | sometimes; array |
| permissions.* | integer | Optional | integer; distinct |

Example payload (IDs must be replaced):
```json
{
  "role": "staff",
  "is_active": true,
  "permissions": [
    1
  ]
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Staff role is business-local. Permission IDs come from data.permissions in the staff list/options response; do not assume IDs are stable across databases.

- Adding an existing email attaches its account without replacing credentials. For a new email, name, phone and a confirmed password of at least 12 characters are required.

- Owner membership is protected. A delegated manager cannot modify self, grant permissions outside their own grant set, or modify a more privileged member. The no-subscription fallback permits one active non-owner staff member.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessStaffController.php:125`
```php
['role' => ['required', Rule::in(array_keys($this->roles()))], 'is_active' => ['required', 'boolean'], 'permissions' => ['sometimes', 'array'], 'permissions.*' => ['integer', 'distinct']]
```

## GET /permissions

List - Permission

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PermissionController.php:13`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.permissions`, `data.roles`, `data.users`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /permissions/assign

Assign - Permission

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PermissionController.php:38`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| assign_type | string | Required | required; in:role,user; Allowed: role, user |
| role_id | id | Conditional | required_if:assign_type,role; nullable; exists:roles,id |
| user_id | id | Conditional | required_if:assign_type,user; nullable; exists:users,id |
| permissions | array | Required | required; array; min:1 |
| permissions.* | string | Optional | exists:permissions,id |

Example payload (IDs must be replaced):
```json
{
  "assign_type": "role",
  "role_id": 1,
  "user_id": 1,
  "permissions": []
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/PermissionController.php:39`
```php
[
            'assign_type' => 'required|in:role,user',
            'role_id' => 'required_if:assign_type,role|nullable|exists:roles,id',
            'user_id' => 'required_if:assign_type,user|nullable|exists:users,id',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,id',
        ]
```

## POST /permissions/store

Create or save - Permission

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PermissionController.php:22`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; unique:permissions,name |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/PermissionController.php:23`
```php
[
            'name' => 'required|string|max:255|unique:permissions,name',
        ]
```

## DELETE /permissions/{permission}

Delete or remove - Permission

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/PermissionController.php:69`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `permission`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /roles

List - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:12`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.roles`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /roles

Create or save - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:28`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; unique:roles,name; not_in:super_admin,admin,owner,user |
| permissions | array | Optional | nullable; array |
| permissions.* | string | Optional | exists:permissions,id |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/RoleController.php:29`
```php
[
            'name' => ['required', 'string', 'max:255', 'unique:roles,name', 'not_in:super_admin,admin,owner,user'],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]
```

## GET /roles/create

Read create form options - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:21`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.permissions`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## DELETE /roles/{role}

Delete or remove - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:101`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `role`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /roles/{role}

Read detail - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:56`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `role`

Domain response data keys: `data.role`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PUT /roles/{role}

Update - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:74`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `role`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; unique:roles,name, |
| permissions | array | Optional | nullable; array |
| permissions.* | string | Optional | exists:permissions,id |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/RoleController.php:77`
```php
[
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]
```

## GET /roles/{role}/edit

Read edit form options - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:63`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `role`

Domain response data keys: `data.role`, `data.permissions`, `data.rolePermissions`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## DELETE /roles/{role}/permissions/{permission}

Remove permission - Role

- Group: 04 Staff roles and permissions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/RoleController.php:123`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `role`, `permission`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /attributes

List - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:22`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.attributes`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /attributes

Create or save - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:57`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; Unique within the server rule scope |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AttributeController.php:63`
```php
[
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name'),
            ],
        ]
```

## DELETE /attributes/{attribute}

Delete or remove - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:125`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `attribute`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /attributes/{attribute}

Update - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:88`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `attribute`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; Unique within the server rule scope |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AttributeController.php:94`
```php
[
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name')
                    ->ignore($attribute->id),
            ],
        ]
```

## POST /attributes/{attribute}/values

Store value - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:146`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `attribute`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| value | string | Required | required; string; max:255; Unique within the server rule scope |
| sort_order | integer | Required | required; integer; min:0; max:999999 |

Example payload (IDs must be replaced):
```json
{
  "value": "demo",
  "sort_order": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AttributeController.php:152`
```php
[
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'value')
                    ->where(
                        'attribute_id',
                        $attribute->id
                    ),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
        ]
```

## DELETE /attributes/{attribute}/values/{value}

Destroy value - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:221`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `attribute`, `value`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /attributes/{attribute}/values/{value}

Update value - Attribute

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AttributeController.php:182`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `attribute`, `value`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| value | string | Required | required; string; max:255; Unique within the server rule scope |
| sort_order | integer | Required | required; integer; min:0; max:999999 |

Example payload (IDs must be replaced):
```json
{
  "value": "demo",
  "sort_order": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AttributeController.php:189`
```php
[
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'value')
                    ->where(
                        'attribute_id',
                        $attribute->id
                    )
                    ->ignore($value->id),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
        ]
```

## POST /businesses/{business}/catalog-attributes/{attribute}/values

Store value - Tenant Catalog

- Group: 05 Categories tags and attributes
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: attributes.manage
- Source: `app/Http/Controllers/TenantCatalogController.php:94`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `attribute`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| value | string | Required | required; string; max:255; Unique within the server rule scope |
| sort_order | integer | Optional | nullable; integer; min:0 |

Example payload (IDs must be replaced):
```json
{
  "value": "22KT",
  "sort_order": 0
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- kind is categories or attributes. categories accepts name, category_type product/service, parent_id, is_active and optional sort_order. attributes accepts name only.

- Catalog item/value must belong to the requested business. Entries in use cannot be deleted.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/TenantCatalogController.php:97`
```php
['value' => ['required', 'string', 'max:255', Rule::unique('attribute_values', 'value')->where('attribute_id', $attribute)], 'sort_order' => ['nullable', 'integer', 'min:0']]
```

## DELETE /businesses/{business}/catalog-attributes/{attribute}/values/{value}

Destroy value - Tenant Catalog

- Group: 05 Categories tags and attributes
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: attributes.manage
- Source: `app/Http/Controllers/TenantCatalogController.php:104`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `attribute`, `value`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- kind is categories or attributes. categories accepts name, category_type product/service, parent_id, is_active and optional sort_order. attributes accepts name only.

- Catalog item/value must belong to the requested business. Entries in use cannot be deleted.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/catalog/{kind}

List - Tenant Catalog

- Group: 05 Categories tags and attributes
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: {kind}.view
- Source: `app/Http/Controllers/TenantCatalogController.php:28`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `kind`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.kind`, `data.items`, `data.parents`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- kind is categories or attributes. categories accepts name, category_type product/service, parent_id, is_active and optional sort_order. attributes accepts name only.

- Catalog item/value must belong to the requested business. Entries in use cannot be deleted.

## POST /businesses/{business}/catalog/{kind}

Create or save - Tenant Catalog

- Group: 05 Categories tags and attributes
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: {kind}.manage
- Source: `app/Http/Controllers/TenantCatalogController.php:64`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `kind`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; Unique within the server rule scope |
| category_type | string | Categories only required | required; Allowed: product, service; Conditional branch; see workflow notes and exact rule |
| parent_id | integer | Categories only optional | nullable; integer; Existing allowed record; referential scope is checked server side; Conditional branch; see workflow notes and exact rule |
| is_active | boolean | Categories only required | required; boolean; Conditional branch; see workflow notes and exact rule |
| sort_order | integer | Categories only optional | nullable; integer; min:0; max:999999; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
```json
{
  "name": "Gold Rings",
  "category_type": "product",
  "is_active": true,
  "sort_order": 0
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- kind is categories or attributes. categories accepts name, category_type product/service, parent_id, is_active and optional sort_order. attributes accepts name only.

- Catalog item/value must belong to the requested business. Entries in use cannot be deleted.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/TenantCatalogController.php:42`
```php
['name' => ['required', 'string', 'max:255', Rule::unique($kind, 'name')->where('business_id', $business->id)->ignore($item?->id)]]
```

`app/Http/Controllers/TenantCatalogController.php:44`
```php
[
                'category_type' => ['required', Rule::in(['product', 'service'])],
                'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('business_id', $business->id)->where('category_type', $request->input('category_type'))->whereNull('parent_id')],
                'is_active' => ['required', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            ]
```

## DELETE /businesses/{business}/catalog/{kind}/{item}

Delete or remove - Tenant Catalog

- Group: 05 Categories tags and attributes
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: {kind}.manage
- Source: `app/Http/Controllers/TenantCatalogController.php:82`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `kind`, `item`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- kind is categories or attributes. categories accepts name, category_type product/service, parent_id, is_active and optional sort_order. attributes accepts name only.

- Catalog item/value must belong to the requested business. Entries in use cannot be deleted.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /businesses/{business}/catalog/{kind}/{item}

Update - Tenant Catalog

- Group: 05 Categories tags and attributes
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: {kind}.manage
- Source: `app/Http/Controllers/TenantCatalogController.php:73`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `kind`, `item`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; Unique within the server rule scope |
| category_type | string | Categories only required | required; Allowed: product, service; Conditional branch; see workflow notes and exact rule |
| parent_id | integer | Categories only optional | nullable; integer; Existing allowed record; referential scope is checked server side; Conditional branch; see workflow notes and exact rule |
| is_active | boolean | Categories only required | required; boolean; Conditional branch; see workflow notes and exact rule |
| sort_order | integer | Categories only optional | nullable; integer; min:0; max:999999; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
```json
{
  "name": "Gold Rings",
  "category_type": "product",
  "is_active": true,
  "sort_order": 0
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- kind is categories or attributes. categories accepts name, category_type product/service, parent_id, is_active and optional sort_order. attributes accepts name only.

- Catalog item/value must belong to the requested business. Entries in use cannot be deleted.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/TenantCatalogController.php:42`
```php
['name' => ['required', 'string', 'max:255', Rule::unique($kind, 'name')->where('business_id', $business->id)->ignore($item?->id)]]
```

`app/Http/Controllers/TenantCatalogController.php:44`
```php
[
                'category_type' => ['required', Rule::in(['product', 'service'])],
                'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('business_id', $business->id)->where('category_type', $request->input('category_type'))->whereNull('parent_id')],
                'is_active' => ['required', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            ]
```

## GET /categories

List - Category

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/CategoryController.php:34`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `search`, `type`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.categories`, `data.types`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /categories

Create or save - Category

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/CategoryController.php:85`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| category_type | string | Required | required |
| parent_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| icon | string | Optional | nullable; string; max:255 |
| image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:2048 |
| is_active | boolean | Required | required; boolean |
| is_featured | boolean | Required | required; boolean |
| sort_order | integer | Required | required; integer; min:0; max:999999 |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "category_type": "demo",
  "is_active": false,
  "is_featured": false,
  "sort_order": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/CategoryController.php:245`
```php
[
            'name' => ['required', 'string', 'max:255'],
            'category_type' => [
                'required',
                Rule::in(array_keys($this->types())),
            ],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('business_id')],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]
```

## GET /categories/create

Read create form options - Category

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/CategoryController.php:68`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.category`, `data.parents`, `data.types`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## DELETE /categories/{category}

Delete or remove - Category

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/CategoryController.php:213`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `category`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /categories/{category}

Update - Category

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/CategoryController.php:146`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `category`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| category_type | string | Required | required |
| parent_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| icon | string | Optional | nullable; string; max:255 |
| image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:2048 |
| is_active | boolean | Required | required; boolean |
| is_featured | boolean | Required | required; boolean |
| sort_order | integer | Required | required; integer; min:0; max:999999 |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "category_type": "demo",
  "is_active": false,
  "is_featured": false,
  "sort_order": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/CategoryController.php:245`
```php
[
            'name' => ['required', 'string', 'max:255'],
            'category_type' => [
                'required',
                Rule::in(array_keys($this->types())),
            ],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('business_id')],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]
```

## GET /categories/{category}/edit

Read edit form options - Category

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/CategoryController.php:128`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `category`

Domain response data keys: `data.category`, `data.parents`, `data.types`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## GET /tags

List - Tag

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/TagController.php:20`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.tags`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /tags

Create or save - Tag

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/TagController.php:40`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; Unique within the server rule scope |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/TagController.php:43`
```php
[
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name'),
            ],
        ]
```

## DELETE /tags/{tag}

Delete or remove - Tag

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/TagController.php:92`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `tag`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /tags/{tag}

Update - Tag

- Group: 05 Categories tags and attributes
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/TagController.php:64`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `tag`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255; Unique within the server rule scope |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/TagController.php:67`
```php
[
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->ignore($tag->id),
            ],
        ]
```

## GET /businesses/{business}/products

List - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductController.php:50`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `search`, `status`, `product_type`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.products`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/products

Create or save - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: products.create
- Source: `app/Http/Controllers/ProductController.php:104`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| stock_status | string | Create only | required; Allowed: in_stock, out_of_stock; Conditional branch; see workflow notes and exact rule |
| stock_quantity | integer | Create only | required; integer; min:0; Conditional branch; see workflow notes and exact rule |
| name | string | Required | required; string; max:255 |
| category_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| sku | string | Optional | nullable; string; max:255; Unique within the server rule scope |
| description | string | Optional | nullable; string; max:20000 |
| price | number | Required | required; numeric; min:0; max:999999999999.99 |
| offer_price | number | Optional | nullable; numeric; min:0; lte:price |
| featured_image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:3072 |
| video | string | Optional | nullable; url:http,https; max:255 |
| status | string | Required | required; Allowed: active, inactive |
| is_featured | boolean | Required | required; boolean |
| is_trending | boolean | Required | required; boolean |
| meta_title | string | Optional | nullable; string; max:255 |
| meta_description | string | Optional | nullable; string; max:1000 |
| meta_keywords | string | Optional | nullable; string; max:1000 |
| specifications | array | Optional | nullable; array; max:30 |
| specifications.*.name | string | Optional | nullable; string; max:255 |
| specifications.*.value | string | Optional | nullable; string; max:1000 |

Example payload (IDs must be replaced):
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

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Required flags is_featured/is_trending accept JSON true/false. price and offer_price are money values; the server remains authoritative.

- Category must be an active product category from global masters or this business.

- On create, stock_status in_stock requires quantity > 0; out_of_stock requires quantity = 0.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductController.php:295`
```php
[
                'stock_status' => [
                    'required',
                    Rule::in(['in_stock', 'out_of_stock']),
                ],
                'stock_quantity' => [
                    'required',
                    'integer',
                    'min:0',
                ],
            ]
```

`app/Http/Controllers/ProductController.php:308`
```php
[
            'name' => ['required', 'string', 'max:255'],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                    ->where('category_type', 'product')
                    ->where('is_active', true),
            ],

            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where('business_id', $business->id)
                    ->withoutTrashed()
                    ->ignore($product?->id),
            ],

            'description' => ['nullable', 'string', 'max:20000'],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'offer_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:price',
            ],

            ...$stockRules,

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'video' => ['nullable', 'url:http,https', 'max:255'],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'is_featured' => ['required', 'boolean'],
            'is_trending' => ['required', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],

            'specifications' => ['nullable', 'array', 'max:30'],
            'specifications.*.name' => ['nullable', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string', 'max:1000'],
        ]
```

## GET /businesses/{business}/products/bulk

List - Product Bulk

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductBulkController.php:48`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.categories`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/products/bulk/errors

Error report - Product Bulk

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductBulkController.php:478`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- SESSION-BOUND WEB COMPATIBILITY ACTION. A new stateless token request has no previous import session, so do not use this for mobile. Use import_errors returned by the import API instead.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses/{business}/products/bulk/export

Export - Product Bulk

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductBulkController.php:91`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## POST /businesses/{business}/products/bulk/import

Import - Product Bulk

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: products.create
- Source: `app/Http/Controllers/ProductBulkController.php:156`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| file | file | Required | required; file; mimes:csv,txt; max:2048 |

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Send CSV in file multipart field. Invalid rows return HTTP 422 with import_errors; no products are imported in that invalid-row branch.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact explicit JSON expression:
```php
json(['message' => 'Invalid CSV rows. No products imported.', 'errors' => ['file' => ['Correct invalid CSV rows.']], 'import_errors' => $errors], 422)
```

Exact validation blocks:

`app/Http/Controllers/ProductBulkController.php:159`
```php
[
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ]
```

## GET /businesses/{business}/products/bulk/template

Template - Product Bulk

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductBulkController.php:64`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses/{business}/products/create

Read create form options - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.create
- Source: `app/Http/Controllers/ProductController.php:93`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.product`, `data.categories`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/products/qr/bulk-print

Bulk print - Product Qr Code

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductQrCodeController.php:116`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| product_ids | array | Required | required; array; min:1; max:50 |
| product_ids.* | integer | Required | required; integer; distinct; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "product_ids": []
}
```

Domain response data keys: `data.business`, `data.products`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductQrCodeController.php:127`
```php
[
            'product_ids' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'product_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('business_id', $business->id)
                    ->whereNull('deleted_at'),
            ],
        ]
```

## DELETE /businesses/{business}/products/{product}

Delete or remove - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.delete
- Source: `app/Http/Controllers/ProductController.php:267`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/products/{product}

Read detail - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductController.php:163`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /businesses/{business}/products/{product}

Update - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: products.update
- Source: `app/Http/Controllers/ProductController.php:195`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| category_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| sku | string | Optional | nullable; string; max:255; Unique within the server rule scope |
| description | string | Optional | nullable; string; max:20000 |
| price | number | Required | required; numeric; min:0; max:999999999999.99 |
| offer_price | number | Optional | nullable; numeric; min:0; lte:price |
| featured_image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:3072 |
| video | string | Optional | nullable; url:http,https; max:255 |
| status | string | Required | required; Allowed: active, inactive |
| is_featured | boolean | Required | required; boolean |
| is_trending | boolean | Required | required; boolean |
| meta_title | string | Optional | nullable; string; max:255 |
| meta_description | string | Optional | nullable; string; max:1000 |
| meta_keywords | string | Optional | nullable; string; max:1000 |
| specifications | array | Optional | nullable; array; max:30 |
| specifications.*.name | string | Optional | nullable; string; max:255 |
| specifications.*.value | string | Optional | nullable; string; max:1000 |

Example payload (IDs must be replaced):
```json
{
  "name": "Gold Ring",
  "category_id": 1,
  "price": 25000,
  "status": "active",
  "is_featured": false,
  "is_trending": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Required flags is_featured/is_trending accept JSON true/false. price and offer_price are money values; the server remains authoritative.

- Category must be an active product category from global masters or this business.

- Stock fields are not product-update inputs. Use the product inventory action to adjust stock.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductController.php:295`
```php
[
                'stock_status' => [
                    'required',
                    Rule::in(['in_stock', 'out_of_stock']),
                ],
                'stock_quantity' => [
                    'required',
                    'integer',
                    'min:0',
                ],
            ]
```

`app/Http/Controllers/ProductController.php:308`
```php
[
            'name' => ['required', 'string', 'max:255'],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                    ->where('category_type', 'product')
                    ->where('is_active', true),
            ],

            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where('business_id', $business->id)
                    ->withoutTrashed()
                    ->ignore($product?->id),
            ],

            'description' => ['nullable', 'string', 'max:20000'],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'offer_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:price',
            ],

            ...$stockRules,

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'video' => ['nullable', 'url:http,https', 'max:255'],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'is_featured' => ['required', 'boolean'],
            'is_trending' => ['required', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],

            'specifications' => ['nullable', 'array', 'max:30'],
            'specifications.*.name' => ['nullable', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string', 'max:1000'],
        ]
```

## GET /businesses/{business}/products/{product}/attributes

List - Product Attribute

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductAttributeController.php:40`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`, `data.attributes`, `data.selectedValues`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /businesses/{business}/products/{product}/attributes

Update - Product Attribute

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductAttributeController.php:76`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| values | array | Optional | sometimes; array |
| values.* | integer | Required | required; integer; distinct; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "values": [
    1,
    2
  ]
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- values is an array of attribute-value IDs. Only global values and this business values are assignable. An omitted/empty values array clears the product mapping.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductAttributeController.php:83`
```php
[
            'values' => ['sometimes', 'array'],
            'values.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('attribute_values', 'id')->whereIn('attribute_id', Attribute::where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))->select('id')),
            ],
        ]
```

## GET /businesses/{business}/products/{product}/edit

Read edit form options - Product

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.update
- Source: `app/Http/Controllers/ProductController.php:183`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`, `data.categories`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/products/{product}/gallery

List - Product Image

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductImageController.php:51`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`, `data.images`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/products/{product}/gallery

Create or save - Product Image

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: products.update
- Source: `app/Http/Controllers/ProductImageController.php:71`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| images | array | Required | required; array; min:1; max:10 |
| images.* | file | Required | required; image; mimes:jpg,jpeg,png,webp; max:3072 |

Example payload (IDs must be replaced):
```json
{
  "images": []
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductImageController.php:78`
```php
[
            'images' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]
```

## DELETE /businesses/{business}/products/{product}/gallery/{image}

Delete or remove - Product Image

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.update
- Source: `app/Http/Controllers/ProductImageController.php:213`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `image`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /businesses/{business}/products/{product}/gallery/{image}

Update - Product Image

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductImageController.php:144`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `image`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| sort_order | integer | Required | required; integer; min:0; max:999999 |
| alt_text | string | Optional | nullable; string; max:255 |

Example payload (IDs must be replaced):
```json
{
  "sort_order": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductImageController.php:153`
```php
[
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]
```

## POST /businesses/{business}/products/{product}/gallery/{image}/featured

Featured - Product Image

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductImageController.php:180`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `image`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/products/{product}/inventory

List - Product Inventory

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: inventory.view
- Source: `app/Http/Controllers/ProductInventoryController.php:42`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.product`, `data.variants`, `data.movements`, `data.lowStockLimit`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/products/{product}/inventory

Create or save - Product Inventory

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: inventory.manage
- Source: `app/Http/Controllers/ProductInventoryController.php:75`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| stock_target | string | Required | required; Allowed: product, variant |
| variant_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| movement_type | string | Required | required; Allowed: stock_in, stock_out, adjustment |
| quantity | integer | Required | required; integer; min:0; max:2147483647 |
| reason | string | Required | required; string; max:255 |

Example payload (IDs must be replaced):
```json
{
  "stock_target": "product",
  "movement_type": "stock_in",
  "quantity": 1,
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductInventoryController.php:82`
```php
[
            'stock_target' => [
                'required',
                Rule::in(['product', 'variant']),
            ],

            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $product->id),
            ],

            'movement_type' => [
                'required',
                Rule::in([
                    'stock_in',
                    'stock_out',
                    'adjustment',
                ]),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:2147483647',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],
        ]
```

## GET /businesses/{business}/products/{product}/qr

Read detail - Product Qr Code

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductQrCodeController.php:41`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`, `data.publicUrl`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/products/{product}/qr

Generate - Product Qr Code

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductQrCodeController.php:56`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/products/{product}/qr/download

Download - Product Qr Code

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductQrCodeController.php:74`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses/{business}/products/{product}/qr/print

Print - Product Qr Code

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductQrCodeController.php:95`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/products/{product}/tags

List - Product Tag

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductTagController.php:40`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`, `data.tags`, `data.selectedTags`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /businesses/{business}/products/{product}/tags

Update - Product Tag

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductTagController.php:65`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| tags | array | Optional | sometimes; array |
| tags.* | integer | Required | required; integer; distinct; Existing allowed record; referential scope is checked server side |

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductTagController.php:72`
```php
[
            'tags' => ['sometimes', 'array'],
            'tags.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tags', 'id'),
            ],
        ]
```

## GET /businesses/{business}/products/{product}/variants

List - Product Variant

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductVariantController.php:51`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.product`, `data.variants`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/products/{product}/variants

Create or save - Product Variant

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: products.update
- Source: `app/Http/Controllers/ProductVariantController.php:72`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| sku | string | Optional | nullable; string; max:255; Unique within the server rule scope |
| price | number | Required | required; numeric; min:0; max:999999999999.99 |
| offer_price | number | Optional | nullable; numeric; min:0; lte:price |
| stock_quantity | integer | Required | required; integer; min:0 |
| status | string | Required | required; Allowed: active, inactive |
| image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:3072 |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "price": 100,
  "stock_quantity": 1,
  "status": "active"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductVariantController.php:239`
```php
[
        'name' => [
            'required',
            'string',
            'max:255'
        ],
        'sku' => [
            'nullable',
            'string',
            'max:255',
            Rule::unique('product_variants', 'sku')
                ->where(function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->ignore($variant?->id),
        ],
        'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999999999.99',
        ],
        'offer_price' => [
            'nullable',
            'numeric',
            'min:0',
            'lte:price',
        ],
        'stock_quantity' => [
            'required',
            'integer',
            'min:0',
        ],
        'status' => [
            'required',
            Rule::in(['active', 'inactive']),
        ],
        'image' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:3072',
        ],
    ]
```

## DELETE /businesses/{business}/products/{product}/variants/{variant}

Delete or remove - Product Variant

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.update
- Source: `app/Http/Controllers/ProductVariantController.php:186`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `variant`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /businesses/{business}/products/{product}/variants/{variant}

Update - Product Variant

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: products.update
- Source: `app/Http/Controllers/ProductVariantController.php:123`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `variant`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| sku | string | Optional | nullable; string; max:255; Unique within the server rule scope |
| price | number | Required | required; numeric; min:0; max:999999999999.99 |
| offer_price | number | Optional | nullable; numeric; min:0; lte:price |
| stock_quantity | integer | Required | required; integer; min:0 |
| status | string | Required | required; Allowed: active, inactive |
| image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:3072 |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "price": 100,
  "stock_quantity": 1,
  "status": "active"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductVariantController.php:239`
```php
[
        'name' => [
            'required',
            'string',
            'max:255'
        ],
        'sku' => [
            'nullable',
            'string',
            'max:255',
            Rule::unique('product_variants', 'sku')
                ->where(function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->ignore($variant?->id),
        ],
        'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999999999.99',
        ],
        'offer_price' => [
            'nullable',
            'numeric',
            'min:0',
            'lte:price',
        ],
        'stock_quantity' => [
            'required',
            'integer',
            'min:0',
        ],
        'status' => [
            'required',
            Rule::in(['active', 'inactive']),
        ],
        'image' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:3072',
        ],
    ]
```

## GET /businesses/{business}/products/{product}/variants/{variant}/attributes

Read edit form options - Product Variant Attribute

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: products.view
- Source: `app/Http/Controllers/ProductVariantAttributeController.php:69`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `variant`

Domain response data keys: `data.business`, `data.product`, `data.variant`, `data.attributes`, `data.selected`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /businesses/{business}/products/{product}/variants/{variant}/attributes

Update - Product Variant Attribute

- Group: 06 Products variants media and inventory
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: products.update
- Source: `app/Http/Controllers/ProductVariantAttributeController.php:101`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`, `variant`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| attributes | array | Optional | sometimes; array |
| attributes.* | integer | Optional | nullable; integer; distinct |

Example payload (IDs must be replaced):
```json
{
  "attributes": {
    "1": 1,
    "2": 2
  }
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- attributes is keyed by attribute ID and its value is an attribute-value ID. Values must already be allowed on the product. Duplicate non-empty variant combinations are rejected.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductVariantAttributeController.php:109`
```php
[
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => [
                'nullable',
                'integer',
                'distinct',
            ],
        ]
```

## GET /businesses/{business}/appointments

List - Appointment

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: appointments.view
- Source: `app/Http/Controllers/AppointmentController.php:56`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; confirmed; Allowed: pending, confirmed, completed, cancelled |
| date_from | string | Optional | nullable; date_format:Y-m-d |
| date_to | string | Optional | nullable; date_format:Y-m-d |
| service_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| search | string | Optional | nullable; string; max:100 |
| status_confirmation | string | Required when status is supplied | same:status; Conditional branch; see workflow notes and exact rule |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.appointments`, `data.stats`, `data.services`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/AppointmentController.php:59`
```php
[
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'confirmed',
                    'completed',
                    'cancelled',
                ]),
            ],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'service_id' => [
                'nullable',
                Rule::exists('services', 'id')
                    ->where('business_id', $business->id),
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]
```

## POST /businesses/{business}/appointments

Create or save - Appointment

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: appointments.manage
- Source: `app/Http/Controllers/AppointmentController.php:247`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| service_id | id | Required | required; Existing allowed record; referential scope is checked server side |
| service_enquiry_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| customer_name | string | Required | required; string; max:255 |
| customer_phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |
| appointment_date | string | Required | required; date_format:Y-m-d; after_or_equal:today |
| appointment_time | string | Required | required; date_format:H:i |
| notes | string | Optional | nullable; string; max:5000 |

Example payload (IDs must be replaced):
```json
{
  "service_id": 1,
  "customer_name": "Demo Customer",
  "customer_phone": "9876543210",
  "appointment_date": "demo",
  "appointment_time": "10:30"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AppointmentController.php:199`
```php
[
            'service_id' => [
                'required',
                Rule::exists('services', 'id')
                    ->where('business_id', $business->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
            ],

            'service_enquiry_id' => [
                'nullable',
                Rule::exists('service_enquiries', 'id')
                    ->where('business_id', $business->id),
            ],

            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'customer_phone' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],

            'appointment_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'appointment_time' => [
                'required',
                'date_format:H:i',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]
```

## GET /businesses/{business}/appointments/create

Read create form options - Appointment

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: appointments.view
- Source: `app/Http/Controllers/AppointmentController.php:176`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `enquiry_id`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.appointment`, `data.services`, `data.enquiry`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/appointments/{appointment}

Read detail - Appointment

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: appointments.view
- Source: `app/Http/Controllers/AppointmentController.php:335`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `appointment`

Domain response data keys: `data.business`, `data.appointment`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/appointments/{appointment}/status

Update status - Appointment

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: appointments.manage
- Source: `app/Http/Controllers/AppointmentController.php:354`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `appointment`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; confirmed; Allowed: pending, confirmed, completed, cancelled |
| status_confirmation | string | Required when status is supplied | same:status; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
```json
{
  "status": "pending",
  "status_confirmation": "StrongPassword123!"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AppointmentController.php:358`
```php
[
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'confirmed',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]
```

## GET /businesses/{business}/services

List - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: services.view
- Source: `app/Http/Controllers/ServiceController.php:65`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `search`, `status`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.services`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/services

Create or save - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: services.create
- Source: `app/Http/Controllers/ServiceController.php:233`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| category_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| description | string | Optional | nullable; string; max:20000 |
| price_type | string | Required | required; Allowed: fixed, starting_from, on_request, free |
| price | number | Optional | nullable; numeric; min:0; max:999999999999.99 |
| offer_price | number | Optional | nullable; numeric; min:0; max:999999999999.99 |
| duration_minutes | integer | Optional | nullable; integer; min:1; max:525600 |
| status | string | Required | required; Allowed: active, inactive |
| featured_image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:3072 |

Example payload (IDs must be replaced):
```json
{
  "name": "Ring resizing",
  "price_type": "fixed",
  "price": 500,
  "duration_minutes": 30,
  "status": "active"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- price_type controls pricing validation; fixed/starting_from require a usable price. Read the field rules and edit/create option data.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ServiceController.php:113`
```php
[
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                    ->where('category_type', 'service')
                    ->where('is_active', true),
            ],

            'description' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'price_type' => [
                'required',
                Rule::in([
                    'fixed',
                    'starting_from',
                    'on_request',
                    'free',
                ]),
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'offer_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:525600',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]
```

## GET /businesses/{business}/services/create

Read create form options - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: services.create
- Source: `app/Http/Controllers/ServiceController.php:101`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.service`, `data.categories`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## DELETE /businesses/{business}/services/{service}

Delete or remove - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: services.delete
- Source: `app/Http/Controllers/ServiceController.php:378`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/services/{service}

Read detail - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: services.view
- Source: `app/Http/Controllers/ServiceController.php:285`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

Domain response data keys: `data.business`, `data.service`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /businesses/{business}/services/{service}

Update - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: services.update
- Source: `app/Http/Controllers/ServiceController.php:319`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| category_id | id | Optional | nullable; Existing allowed record; referential scope is checked server side |
| description | string | Optional | nullable; string; max:20000 |
| price_type | string | Required | required; Allowed: fixed, starting_from, on_request, free |
| price | number | Optional | nullable; numeric; min:0; max:999999999999.99 |
| offer_price | number | Optional | nullable; numeric; min:0; max:999999999999.99 |
| duration_minutes | integer | Optional | nullable; integer; min:1; max:525600 |
| status | string | Required | required; Allowed: active, inactive |
| featured_image | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:3072 |

Example payload (IDs must be replaced):
```json
{
  "name": "Ring resizing",
  "price_type": "fixed",
  "price": 500,
  "duration_minutes": 30,
  "status": "active"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- price_type controls pricing validation; fixed/starting_from require a usable price. Read the field rules and edit/create option data.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ServiceController.php:113`
```php
[
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                    ->where('category_type', 'service')
                    ->where('is_active', true),
            ],

            'description' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'price_type' => [
                'required',
                Rule::in([
                    'fixed',
                    'starting_from',
                    'on_request',
                    'free',
                ]),
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'offer_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:525600',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]
```

## GET /businesses/{business}/services/{service}/edit

Read edit form options - Service

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: services.update
- Source: `app/Http/Controllers/ServiceController.php:304`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

Domain response data keys: `data.business`, `data.service`, `data.categories`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/services/{service}/images

Create or save - Service Image

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: services.update
- Source: `app/Http/Controllers/ServiceImageController.php:40`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| images | array | Required | required; array; min:1; max:10 |
| images.* | file | Required | required; image; mimes:jpg,jpeg,png,webp; max:3072 |

Example payload (IDs must be replaced):
```json
{
  "images": []
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ServiceImageController.php:43`
```php
[
            'images' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]
```

## DELETE /businesses/{business}/services/{service}/images/{image}

Delete or remove - Service Image

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: services.update
- Source: `app/Http/Controllers/ServiceImageController.php:123`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`, `image`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /businesses/{business}/services/{service}/images/{image}/featured

Featured - Service Image

- Group: 07 Services and appointments
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: services.update
- Source: `app/Http/Controllers/ServiceImageController.php:101`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `service`, `image`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/gallery

List - Business Gallery

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: gallery.view
- Source: `app/Http/Controllers/BusinessGalleryController.php:57`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| media_type | string | Optional | nullable |
| status | string | Optional | nullable; Allowed: active, inactive |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.galleries`, `data.counts`, `data.total`, `data.types`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/BusinessGalleryController.php:63`
```php
[
            'media_type' => [
                'nullable',
                Rule::in(array_keys($this->types())),
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
        ]
```

## POST /businesses/{business}/gallery

Create or save - Business Gallery

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: gallery.manage
- Source: `app/Http/Controllers/BusinessGalleryController.php:135`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| media_type | string | Required | required |
| title | string | Optional | nullable; string; max:255 |
| description | string | Optional | nullable; string; max:5000 |
| sort_order | integer | Required | required; integer; min:0 |
| is_active | boolean | Required | required; boolean |
| file | file | Required | required; file; max:102400 |

Example payload (IDs must be replaced):
```json
{
  "media_type": "demo",
  "sort_order": 1,
  "is_active": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessGalleryController.php:141`
```php
[
            'media_type' => [
                'required',
                Rule::in(array_keys($this->types())),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'file' => [
                'required',
                'file',
                'max:102400',
            ],
        ]
```

## GET /businesses/{business}/gallery/create

Read create form options - Business Gallery

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: gallery.view
- Source: `app/Http/Controllers/BusinessGalleryController.php:117`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.gallery`, `data.types`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## DELETE /businesses/{business}/gallery/{gallery}

Delete or remove - Business Gallery

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: gallery.manage
- Source: `app/Http/Controllers/BusinessGalleryController.php:254`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `gallery`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /businesses/{business}/gallery/{gallery}

Update - Business Gallery

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: gallery.manage
- Source: `app/Http/Controllers/BusinessGalleryController.php:223`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `gallery`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| title | string | Optional | nullable; string; max:255 |
| description | string | Optional | nullable; string; max:5000 |
| sort_order | integer | Required | required; integer; min:0 |
| is_active | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "sort_order": 1,
  "is_active": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessGalleryController.php:230`
```php
[
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]
```

## GET /businesses/{business}/gallery/{gallery}/edit

Read edit form options - Business Gallery

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: gallery.view
- Source: `app/Http/Controllers/BusinessGalleryController.php:206`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `gallery`

Domain response data keys: `data.business`, `data.gallery`, `data.types`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/hours

List - Business Hour

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: business.hours.manage
- Source: `app/Http/Controllers/BusinessHourController.php:17`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.hours`, `data.days`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/hours

Create or save - Business Hour

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: business.hours.manage
- Source: `app/Http/Controllers/BusinessHourController.php:45`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| hours | array | Required | required; array; size:7 |
| hours.*.open_time | string | Optional | nullable; date_format:H:i |
| hours.*.close_time | string | Optional | nullable; date_format:H:i |
| hours.*.is_closed | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "hours": [
    {
      "is_closed": false
    }
  ]
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessHourController.php:48`
```php
[
            'hours' => ['required', 'array', 'size:7'],

            'hours.*.open_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'hours.*.close_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'hours.*.is_closed' => [
                'required',
                'boolean',
            ],
        ]
```

## GET /businesses/{business}/offers

List - Business Offer

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: offers.view
- Source: `app/Http/Controllers/BusinessOfferController.php:63`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.offers`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/offers

Create or save - Business Offer

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: offers.manage
- Source: `app/Http/Controllers/BusinessOfferController.php:78`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| title | string | Required | required; string; max:255 |
| description | string | Optional | nullable; string; max:5000 |
| offer_type | string | Required | required; Allowed: percentage, fixed, announcement |
| discount_value | number | Conditional | nullable; numeric; min:0; max:999999999999.99; required_unless:offer_type,announcement |
| starts_at | string | Optional | nullable; date_format:Y-m-d |
| ends_at | string | Optional | nullable; date_format:Y-m-d; after_or_equal:starts_at |
| status | string | Required | required; Allowed: active, inactive |
| banner_file | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:5120 |
| product_ids | array | Optional | sometimes; array; max:100 |
| product_ids.* | integer | Optional | integer; distinct; Existing allowed record; referential scope is checked server side |
| service_ids | array | Optional | sometimes; array; max:100 |
| service_ids.* | integer | Optional | integer; distinct; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "title": "demo",
  "offer_type": "percentage",
  "discount_value": 100,
  "status": "active"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessOfferController.php:39`
```php
[
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'offer_type' => ['required', Rule::in(['percentage', 'fixed', 'announcement'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'required_unless:offer_type,announcement'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'product_ids' => ['sometimes', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
            'service_ids' => ['sometimes', 'array', 'max:100'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
        ]
```

## GET /businesses/{business}/offers/create

Read create form options - Business Offer

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: offers.view
- Source: `app/Http/Controllers/BusinessOfferController.php:71`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.offer`, `data.selectedProducts`, `data.selectedServices`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## DELETE /businesses/{business}/offers/{offer}

Delete or remove - Business Offer

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: offers.manage
- Source: `app/Http/Controllers/BusinessOfferController.php:155`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `offer`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /businesses/{business}/offers/{offer}

Update - Business Offer

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: offers.manage
- Source: `app/Http/Controllers/BusinessOfferController.php:118`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `offer`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| title | string | Required | required; string; max:255 |
| description | string | Optional | nullable; string; max:5000 |
| offer_type | string | Required | required; Allowed: percentage, fixed, announcement |
| discount_value | number | Conditional | nullable; numeric; min:0; max:999999999999.99; required_unless:offer_type,announcement |
| starts_at | string | Optional | nullable; date_format:Y-m-d |
| ends_at | string | Optional | nullable; date_format:Y-m-d; after_or_equal:starts_at |
| status | string | Required | required; Allowed: active, inactive |
| banner_file | file | Optional | nullable; image; mimes:jpg,jpeg,png,webp; max:5120 |
| product_ids | array | Optional | sometimes; array; max:100 |
| product_ids.* | integer | Optional | integer; distinct; Existing allowed record; referential scope is checked server side |
| service_ids | array | Optional | sometimes; array; max:100 |
| service_ids.* | integer | Optional | integer; distinct; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "title": "demo",
  "offer_type": "percentage",
  "discount_value": 100,
  "status": "active"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessOfferController.php:39`
```php
[
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'offer_type' => ['required', Rule::in(['percentage', 'fixed', 'announcement'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'required_unless:offer_type,announcement'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'product_ids' => ['sometimes', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
            'service_ids' => ['sometimes', 'array', 'max:100'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
        ]
```

## GET /businesses/{business}/offers/{offer}/edit

Read edit form options - Business Offer

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: offers.view
- Source: `app/Http/Controllers/BusinessOfferController.php:106`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `offer`

Domain response data keys: `data.business`, `data.offer`, `data.selectedProducts`, `data.selectedServices`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/social-links

List - Business Social Link

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: business.social.manage
- Source: `app/Http/Controllers/BusinessSocialLinkController.php:27`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.platforms`, `data.links`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/social-links

Create or save - Business Social Link

- Group: 08 Business profile content and offers
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: business.social.manage
- Source: `app/Http/Controllers/BusinessSocialLinkController.php:43`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| links | array | Required | required; array |
| links.*.url | string | Optional | nullable; string; url:http,https; max:2000 |
| links.*.username | string | Optional | nullable; string; max:255 |

Example payload (IDs must be replaced):
```json
{
  "links": []
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessSocialLinkController.php:48`
```php
[
            'links' => ['required', 'array'],
            'links.*.url' => [
                'nullable',
                'string',
                'url:http,https',
                'max:2000',
            ],
            'links.*.username' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]
```

## GET /my-addresses

List - Customer Address

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCustomerAddressController.php:13`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.addresses`, `data.states`.

## POST /my-addresses

Create or save - Customer Address

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCustomerAddressController.php:55`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| label | string | Required | required; Allowed: Home, Office, Other |
| recipient_name | string | Required | required; string; max:255 |
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |
| address | string | Required | required; string; max:1000 |
| state_id | integer | Required | required; integer; Existing allowed record; referential scope is checked server side |
| city_name | string | Required | required; string; max:100 |
| pincode | string | Required | required; regex:/^[1-9][0-9]{5}$/ |
| is_default | boolean | Optional | nullable; boolean |

Example payload (IDs must be replaced):
```json
{
  "label": "Home",
  "recipient_name": "demo",
  "phone": "9876543210",
  "address": "Demo street address",
  "state_id": 1,
  "city_name": "Kanpur",
  "pincode": "208001"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCustomerAddressController.php:22`
```php
[
            'label'=>['required',Rule::in(['Home','Office','Other'])],
            'recipient_name'=>['required','string','max:255'],
            'phone'=>['required','regex:/^[6-9][0-9]{9}$/'],
            'address'=>['required','string','max:1000'],
            'state_id'=>['required','integer',Rule::exists('states','id')->where('is_active',true)],
            'city_name'=>['required','string','max:100'],
            'pincode'=>['required','regex:/^[1-9][0-9]{5}$/'],
            'is_default'=>['nullable','boolean'],
        ]
```

## DELETE /my-addresses/{address}

Delete or remove - Customer Address

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCustomerAddressController.php:99`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `address`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PUT /my-addresses/{address}

Update - Customer Address

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCustomerAddressController.php:71`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `address`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| label | string | Required | required; Allowed: Home, Office, Other |
| recipient_name | string | Required | required; string; max:255 |
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |
| address | string | Required | required; string; max:1000 |
| state_id | integer | Required | required; integer; Existing allowed record; referential scope is checked server side |
| city_name | string | Required | required; string; max:100 |
| pincode | string | Required | required; regex:/^[1-9][0-9]{5}$/ |
| is_default | boolean | Optional | nullable; boolean |

Example payload (IDs must be replaced):
```json
{
  "label": "Home",
  "recipient_name": "demo",
  "phone": "9876543210",
  "address": "Demo street address",
  "state_id": 1,
  "city_name": "Kanpur",
  "pincode": "208001"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCustomerAddressController.php:22`
```php
[
            'label'=>['required',Rule::in(['Home','Office','Other'])],
            'recipient_name'=>['required','string','max:255'],
            'phone'=>['required','regex:/^[6-9][0-9]{9}$/'],
            'address'=>['required','string','max:1000'],
            'state_id'=>['required','integer',Rule::exists('states','id')->where('is_active',true)],
            'city_name'=>['required','string','max:100'],
            'pincode'=>['required','regex:/^[1-9][0-9]{5}$/'],
            'is_default'=>['nullable','boolean'],
        ]
```

## PATCH /my-addresses/{address}/default

Set default - Customer Address

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCustomerAddressController.php:88`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `address`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /my-orders

My orders - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:173`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.orders`.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

## GET /my-orders/{order}

Order - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:180`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `order`

Domain response data keys: `data.order`.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

## GET /shops/{business}/cart

List - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:37`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.cart`.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

## POST /shops/{business}/cart

Add - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:45`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| product_id | integer | Required | required; integer; Existing allowed record; referential scope is checked server side |
| variant_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| quantity | integer | Required | required; integer; min:1; max:100 |

Example payload (IDs must be replaced):
```json
{
  "product_id": 1,
  "quantity": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCartCheckoutController.php:47`
```php
[
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
            'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('status', 'active')],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]
```

## DELETE /shops/{business}/cart/{item}

Remove - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:92`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `item`

Response: `message` and web `location`; no guaranteed resource object.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /shops/{business}/cart/{item}

Update - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:77`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `item`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| quantity | integer | Required | required; integer; min:1; max:100 |

Example payload (IDs must be replaced):
```json
{
  "quantity": 2
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCartCheckoutController.php:81`
```php
['quantity' => ['required', 'integer', 'min:1', 'max:100']]
```

## GET /shops/{business}/checkout

Checkout form - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:101`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.cart`, `data.addresses`.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## POST /shops/{business}/checkout

Place - Cart Checkout

- Group: 09 Customer cart checkout and addresses
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaCartCheckoutController.php:113`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| quoted_total_paise | integer | Optional | nullable; integer; min:0 |
| address_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| customer_name | string | Conditional | required_without:address_id; nullable; string; max:255 |
| customer_phone | string | Conditional | required_without:address_id; nullable; regex:/^[6-9][0-9]{9}$/ |
| address | string | Conditional | required_without:address_id; nullable; string; max:500 |
| city | string | Conditional | required_without:address_id; nullable; string; max:100 |
| state | string | Conditional | required_without:address_id; nullable; string; max:100 |
| pincode | string | Conditional | required_without:address_id; nullable; regex:/^[1-9][0-9]{5}$/ |
| payment_method | string | Required | required |
| notes | string | Optional | nullable; string; max:2000 |
| coupon_code | string | Optional | nullable; string; max:40 |
| save_address | boolean | Optional | nullable; boolean |
| address_label | string | Optional | nullable; Allowed: Home, Office, Other |

Example payload (IDs must be replaced):
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

Response: `message` and web `location`; no guaranteed resource object.

- Cart is scoped by authenticated customer and business. Use an approved active business; cart_item IDs are not product IDs.

- Provide address_id owned by the customer, or manual customer_name, customer_phone, address, city, state and pincode. A saved address supplies authoritative snapshot fields.

- payment_method is cod or upi_manual. This records order intent; it is not gateway payment settlement. Response location is the original web order URL; navigate in Flutter using the API route and ID.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCartCheckoutController.php:115`
```php
[
            'quoted_total_paise' => ['nullable', 'integer', 'min:0'],
            'address_id' => ['nullable', 'integer', Rule::exists('user_addresses', 'id')->where('user_id', auth()->id())],
            'customer_name' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:address_id', 'nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'address' => ['required_without:address_id', 'nullable', 'string', 'max:500'],
            'city' => ['required_without:address_id', 'nullable', 'string', 'max:100'],
            'state' => ['required_without:address_id', 'nullable', 'string', 'max:100'],
            'pincode' => ['required_without:address_id', 'nullable', 'regex:/^[1-9][0-9]{5}$/'],
            'payment_method' => ['required', Rule::in(config('growya_extensions.razorpay.enabled') ? ['cod', 'upi_manual', 'razorpay'] : ['cod', 'upi_manual'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'save_address' => ['nullable', 'boolean'],
            'address_label' => ['nullable', Rule::in(['Home', 'Office', 'Other'])],
        ]
```

## GET /businesses/{business}/delivery

List - Delivery Tracking

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: delivery.view
- Source: `app/Http/Controllers/GrowyaDeliveryTrackingController.php:59`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| filter | string | Optional | nullable; Allowed: all, dispatch, transit, delivered |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.orders`, `data.filter`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaDeliveryTrackingController.php:61`
```php
['filter' => ['nullable', Rule::in(['all', 'dispatch', 'transit', 'delivered'])]]
```

## GET /businesses/{business}/orders

List - Business Order

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: orders.view
- Source: `app/Http/Controllers/GrowyaBusinessOrderController.php:27`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; confirmed; Allowed: pending, confirmed, shipped, delivered, cancelled |
| search | string | Optional | nullable; string; max:100 |
| status_confirmation | string | Required when status is supplied | same:status; Conditional branch; see workflow notes and exact rule |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.orders`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessOrderController.php:29`
```php
['status' => ['nullable', Rule::in(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])], 'search' => ['nullable', 'string', 'max:100']]
```

## GET /businesses/{business}/orders/{order}

Read detail - Business Order

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: orders.view
- Source: `app/Http/Controllers/GrowyaBusinessOrderController.php:43`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `order`

Domain response data keys: `data.business`, `data.order`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/orders/{order}/delivery

Seller show - Delivery Tracking

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: delivery.view
- Source: `app/Http/Controllers/GrowyaDeliveryTrackingController.php:51`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `order`

Domain response data keys: `data.business`, `data.order`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/orders/{order}/delivery

Update - Delivery Tracking

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: delivery.manage
- Source: `app/Http/Controllers/GrowyaDeliveryTrackingController.php:78`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| stage | string | Required | required; Allowed: placed, confirmed, packed, shipped, out_for_delivery, delivered |
| courier_name | string | Optional | nullable; string; max:120 |
| tracking_number | string | Optional | nullable; string; max:120 |
| tracking_url | string | Optional | nullable; url; max:2048 |
| estimated_delivery_date | string | Optional | nullable; date_format:Y-m-d |
| note | string | Optional | nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "stage": "placed"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaDeliveryTrackingController.php:80`
```php
[
            'stage' => ['required', Rule::in(self::STAGES)],
            'courier_name' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'tracking_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
            'estimated_delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]
```

## GET /businesses/{business}/orders/{order}/payment/{submission}/proof

Proof - Payment

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: payments.view
- Source: `app/Http/Controllers/GrowyaPaymentController.php:142`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `order`, `submission`

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## PATCH /businesses/{business}/orders/{order}/payment/{submission}/review

Review - Payment

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: payments.manage
- Source: `app/Http/Controllers/GrowyaPaymentController.php:109`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `order`, `submission`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; in:approve,reject; Allowed: approve, reject |
| reason | string | Conditional | required_if:decision,reject; nullable; string; min:5; max:1000 |

Example payload (IDs must be replaced):
```json
{
  "decision": "approve",
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaymentController.php:112`
```php
['decision' => ['required', 'in:approve,reject'], 'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:1000']]
```

## PATCH /businesses/{business}/orders/{order}/status

Status - Business Order

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: orders.manage
- Source: `app/Http/Controllers/GrowyaBusinessOrderController.php:52`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; confirmed; Allowed: confirmed, shipped, delivered, cancelled |
| pending | string | Optional | confirmed |
| status_confirmation | string | Required when status is supplied | same:status; Conditional branch; see workflow notes and exact rule |
| pending_confirmation | string | Required when pending is supplied | same:pending; Conditional branch; see workflow notes and exact rule |

Example payload (IDs must be replaced):
```json
{
  "status": "confirmed"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Standard transitions: pending -> confirmed -> shipped -> delivered; cancel uses the order service and its current-state checks. Seller changes require orders.manage.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessOrderController.php:55`
```php
['status' => ['required', Rule::in(['confirmed', 'shipped', 'delivered', 'cancelled'])]]
```

`app/Http/Controllers/GrowyaBusinessOrderController.php:61`
```php
['pending' => ['confirmed'], 'confirmed' => ['shipped'], 'shipped' => ['delivered']]
```

## GET /businesses/{business}/payments

Seller index - Payment

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: payments.view
- Source: `app/Http/Controllers/GrowyaPaymentController.php:96`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; in:pending,approved,rejected; Allowed: pending, approved, rejected |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.submissions`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaymentController.php:98`
```php
['status' => ['nullable', 'in:pending,approved,rejected']]
```

## GET /businesses/{business}/returns

Seller index - Order Returns

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: returns.view
- Source: `app/Http/Controllers/GrowyaOrderReturnsController.php:65`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; Allowed: pending, approved, rejected, refunded, resolved |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.requests`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaOrderReturnsController.php:67`
```php
['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'refunded', 'resolved'])]]
```

## PATCH /businesses/{business}/returns/{returnRequest}/refund

Mark refunded - Order Returns

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: returns.manage
- Source: `app/Http/Controllers/GrowyaOrderReturnsController.php:109`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `returnRequest`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| method | string | Required | required; Allowed: manual_upi, bank_transfer, other |
| reference | string | Required | required; string; min:6; max:120; regex:/^[A-Za-z0-9_\-\/]+$/; unique:order_refund_records,reference |
| note | string | Optional | nullable; string; max:1000 |

Example payload (IDs must be replaced):
```json
{
  "method": "manual_upi",
  "reference": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaOrderReturnsController.php:111`
```php
[
            'method' => ['required', Rule::in(['manual_upi', 'bank_transfer', 'other'])],
            'reference' => ['required', 'string', 'min:6', 'max:120', 'regex:/^[A-Za-z0-9_\-\/]+$/', 'unique:order_refund_records,reference'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]
```

## PATCH /businesses/{business}/returns/{returnRequest}/review

Review - Order Returns

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: returns.manage
- Source: `app/Http/Controllers/GrowyaOrderReturnsController.php:77`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `returnRequest`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; Allowed: approve, reject |
| seller_note | string | Conditional | required_if:decision,reject; nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "decision": "approve",
  "seller_note": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaOrderReturnsController.php:79`
```php
[
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'seller_note' => ['required_if:decision,reject', 'nullable', 'string', 'max:2000'],
        ]
```

## GET /my-orders/{order}/payment

Customer index - Payment

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaymentController.php:43`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `order`

Domain response data keys: `data.order`, `data.submissions`.

## POST /my-orders/{order}/payment

Submit - Payment

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaymentController.php:51`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| utr | string | Required | required; string; regex:/^[A-Za-z0-9-]{6,100}$/; unique:order_payment_submissions,utr |
| proof | file | Optional | nullable; file; mimes:jpg,jpeg,png,webp,pdf; max:5120 |

Example payload (IDs must be replaced):
```json
{
  "utr": "UTR-DEMO-000001"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- utr is required, unique and 6-100 alphanumeric/hyphen characters. proof is optional JPG/JPEG/PNG/WEBP/PDF up to 5120 KB. Only eligible pending manual-UPI customer orders accept a submission; a pending proof blocks another submission.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaymentController.php:53`
```php
[
            'utr' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{6,100}$/', 'unique:order_payment_submissions,utr'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]
```

## GET /my-orders/{order}/payment/{submission}/proof

Customer proof - Payment

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaymentController.php:152`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `order`, `submission`

Response: binary/stream download.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /my-orders/{order}/returns

Customer index - Order Returns

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaOrderReturnsController.php:28`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `order`

Domain response data keys: `data.order`, `data.requests`.

## POST /my-orders/{order}/returns

Submit - Order Returns

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaOrderReturnsController.php:36`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| type | string | Required | required; Allowed: cancellation, return |
| reason | string | Required | required; string; min:10; max:2500 |

Example payload (IDs must be replaced):
```json
{
  "type": "cancellation",
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaOrderReturnsController.php:38`
```php
[
            'type' => ['required', Rule::in(['cancellation', 'return'])],
            'reason' => ['required', 'string', 'min:10', 'max:2500'],
        ]
```

## GET /my-orders/{order}/tracking

Customer - Delivery Tracking

- Group: 10 Orders payments returns and delivery
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaDeliveryTrackingController.php:43`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `order`

Domain response data keys: `data.order`.

## GET /admin/lead-insights

Admin - Lead Insights

- Group: 11 Leads CRM and staff performance
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaLeadInsightsController.php:123`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |
| source | string | Optional | nullable; in:all,product_enquiry,service_enquiry,wishlist,chat; Allowed: all, product_enquiry, service_enquiry, wishlist, chat |
| status | string | Optional | nullable; in:all,new,contacted,follow_up,converted,closed; Allowed: all, new, contacted, follow_up, converted, closed |

Response: JSON object; see the native account/permissions contract or original controller payload.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaLeadInsightsController.php:16`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:all,product_enquiry,service_enquiry,wishlist,chat'],
            'status' => ['nullable', 'in:all,new,contacted,follow_up,converted,closed'],
        ]
```

## GET /admin/lead-insights/export

Admin export - Lead Insights

- Group: 11 Leads CRM and staff performance
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaLeadInsightsController.php:161`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |
| source | string | Optional | nullable; in:all,product_enquiry,service_enquiry,wishlist,chat; Allowed: all, product_enquiry, service_enquiry, wishlist, chat |
| status | string | Optional | nullable; in:all,new,contacted,follow_up,converted,closed; Allowed: all, new, contacted, follow_up, converted, closed |

Response: JSON object; see the native account/permissions contract or original controller payload.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaLeadInsightsController.php:16`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:all,product_enquiry,service_enquiry,wishlist,chat'],
            'status' => ['nullable', 'in:all,new,contacted,follow_up,converted,closed'],
        ]
```

## GET /businesses/{business}/crm

List - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:117`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `q`, `overdue`, `status`, `source`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.contacts`, `data.totals`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/crm

Create or save - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:150`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:150 |
| email | string | Optional | nullable; email; max:190; Unique within the server rule scope |
| phone | string | Optional | nullable; string; max:30 |
| company | string | Optional | nullable; string; max:190 |
| status | string | Required | required; Allowed: new, contacted, interested, converted, closed |
| source | string | Required | required; Allowed: manual, import, product_enquiry, service_enquiry, wishlist, chat, other |
| tags | string | Optional | nullable; string; max:500 |
| next_follow_up_at | date | Optional | nullable; date |
| notes | string | Optional | nullable; string; max:10000 |
| assigned_to | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "status": "new",
  "source": "manual"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessCrmController.php:46`
```php
[
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                // Email is unique per business (same rule the CSV import follows).
                Rule::unique((new GrowyaCrmContact)->getTable(), 'email')
                    ->where('business_id', $business->id)->ignore($ignoreId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:190'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'source' => ['required', Rule::in(self::SOURCES)],
            'tags' => ['nullable', 'string', 'max:500'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('business_users', 'user_id')
                ->where('business_id', $business->id)->where('is_active', true)],
        ]
```

## GET /businesses/{business}/crm-incentives

List - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: incentives.view
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:56`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | Allowed: pending, approved, rejected, paid |

Additional read/query inputs: `staff_id`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.manager`, `data.staff`, `data.rules`, `data.claims`, `data.summary`, `data.audits`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmIncentiveController.php:76`
```php
['status' => Rule::in(['pending', 'approved', 'rejected', 'paid'])]
```

## POST /businesses/{business}/crm-incentives/claims

Claim - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: incentives.manage
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:121`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| rule_id | integer | Required | required; integer |
| staff_user_id | integer | Required | required; integer |
| contact_id | integer | Optional | nullable; integer |
| base_amount | number | Optional | nullable; numeric; min:0; max:999999999999 |
| source_reference | string | Optional | nullable; string; max:160 |
| note | string | Optional | nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "rule_id": 1,
  "staff_user_id": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmIncentiveController.php:123`
```php
[
            'rule_id' => ['required', 'integer'],
            'staff_user_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'base_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'source_reference' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]
```

## PATCH /businesses/{business}/crm-incentives/claims/{claim}/pay

Pay - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: incentives.manage
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:215`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `claim`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| payment_reference | string | Required | required; string; max:160 |
| note | string | Optional | nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "payment_reference": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmIncentiveController.php:217`
```php
['payment_reference' => ['required', 'string', 'max:160'], 'note' => ['nullable', 'string', 'max:2000']]
```

## PATCH /businesses/{business}/crm-incentives/claims/{claim}/review

Review - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: incentives.manage
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:191`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `claim`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; Allowed: approve, reject |
| note | string | Optional | nullable; string; max:2000 |

Additional read/query inputs: `payment_verified`. These are read by the controller; not all have explicit validator limits.

Example payload (IDs must be replaced):
```json
{
  "decision": "approve"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmIncentiveController.php:193`
```php
['decision' => ['required', Rule::in(['approve', 'reject'])], 'note' => ['nullable', 'string', 'max:2000']]
```

## GET /businesses/{business}/crm-incentives/export

Export - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: incentives.view
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:268`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `id`, `staff`, `basis`, `status`, `incentive_amount`, `base_amount`, `source_reference`, `payment_reference`, `created_at`, `paid_at`. These are read by the controller; not all have explicit validator limits.

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses/{business}/crm-incentives/report

Report - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: incentives.view
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:235`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d; after_or_equal:from |

Domain response data keys: `data.business`, `data.reports`, `data.from`, `data.to`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmIncentiveController.php:237`
```php
['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]
```

## POST /businesses/{business}/crm-incentives/rules

Rule - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: incentives.manage
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:92`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:120 |
| basis | string | Required | required; Allowed: crm_conversion, manually_verified_sale |
| calculation | string | Required | required; Allowed: fixed, percentage |
| amount | number | Required | required; numeric; max:1000000 |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "basis": "crm_conversion",
  "calculation": "fixed",
  "amount": 100
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmIncentiveController.php:94`
```php
[
            'name' => ['required', 'string', 'max:120'],
            'basis' => ['required', Rule::in(['crm_conversion', 'manually_verified_sale'])],
            'calculation' => ['required', Rule::in(['fixed', 'percentage'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ]
```

## PATCH /businesses/{business}/crm-incentives/rules/{rule}/toggle

Toggle rule - Crm Incentive

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: incentives.manage
- Source: `app/Http/Controllers/GrowyaCrmIncentiveController.php:109`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `rule`

Additional read/query inputs: `id`, `active`. These are read by the controller; not all have explicit validator limits.

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/crm-kanban

List - Crm Kanban

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaCrmKanbanController.php:52`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| staff_id | integer | Optional | nullable; integer |
| priority | string | Optional | nullable; Allowed: low, medium, high |
| q | string | Optional | nullable; string; max:120 |
| overdue | boolean | Optional | nullable; boolean |

Domain response data keys: `data.business`, `data.manager`, `data.staff`, `data.columns`, `data.counts`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmKanbanController.php:54`
```php
[
            'staff_id' => ['nullable', 'integer'],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'q' => ['nullable', 'string', 'max:120'],
            'overdue' => ['nullable', 'boolean'],
        ]
```

## PATCH /businesses/{business}/crm-kanban/{contact}

Update - Crm Kanban

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaCrmKanbanController.php:100`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: new, contacted, interested, converted, closed |
| priority | string | Optional | nullable; Allowed: low, medium, high |
| next_follow_up_at | date | Optional | sometimes; nullable; date |
| note | string | Optional | nullable; string; max:3000 |

Example payload (IDs must be replaced):
```json
{
  "status": "new"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact explicit JSON expression:
```php
json(['ok' => true, 'changes' => $changed])
```

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmKanbanController.php:102`
```php
[
            'status' => ['required', Rule::in(self::STAGES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'next_follow_up_at' => ['sometimes', 'nullable', 'date'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]
```

## GET /businesses/{business}/crm-staff

Dashboard - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:71`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `staff_id`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.manager`, `data.totals`, `data.due`, `data.leads`, `data.staff`, `data.targets`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/crm-staff/report

Report - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:205`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | date | Optional | nullable; date |
| to | date | Optional | nullable; date; after_or_equal:from |

Domain response data keys: `data.business`, `data.reports`, `data.from`, `data.to`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmStaffController.php:207`
```php
['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]
```

## POST /businesses/{business}/crm-staff/targets

Target - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.assign
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:171`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| user_id | integer | Required | required; integer; Existing allowed record; referential scope is checked server side |
| period | string | Required | required; Allowed: daily, weekly, monthly |
| period_start | string | Required | required; date_format:Y-m-d |
| calls_target | integer | Required | required; integer; min:0; max:100000 |
| followups_target | integer | Required | required; integer; min:0; max:100000 |
| conversions_target | integer | Required | required; integer; min:0; max:100000 |

Example payload (IDs must be replaced):
```json
{
  "user_id": 1,
  "period": "daily",
  "period_start": "2026-10-20",
  "calls_target": 1,
  "followups_target": 1,
  "conversions_target": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmStaffController.php:173`
```php
[
            'user_id' => ['required', 'integer', Rule::exists('business_users', 'user_id')->where('business_id', $business->id)->where('is_active', true)],
            'period' => ['required', Rule::in(self::PERIODS)],
            'period_start' => ['required', 'date_format:Y-m-d'],
            'calls_target' => ['required', 'integer', 'min:0', 'max:100000'],
            'followups_target' => ['required', 'integer', 'min:0', 'max:100000'],
            'conversions_target' => ['required', 'integer', 'min:0', 'max:100000'],
        ]
```

## GET /businesses/{business}/crm-staff/{contact}

Read detail - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:96`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

Domain response data keys: `data.business`, `data.customer`, `data.staff`, `data.manager`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/crm-staff/{contact}/activities

Activity - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:156`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| type | string | Required | required; Allowed: call, meeting, follow_up, note |
| description | string | Required | required; string; max:5000 |

Example payload (IDs must be replaced):
```json
{
  "type": "call",
  "description": "Demo text"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmStaffController.php:158`
```php
[
            'type' => ['required', Rule::in(['call', 'meeting', 'follow_up', 'note'])],
            'description' => ['required', 'string', 'max:5000'],
        ]
```

## PATCH /businesses/{business}/crm-staff/{contact}/assign

Assign - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.assign
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:107`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| assigned_to | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmStaffController.php:109`
```php
['assigned_to' => ['nullable', 'integer', Rule::exists('business_users', 'user_id')
            ->where('business_id', $business->id)->where('is_active', true)]]
```

## PATCH /businesses/{business}/crm-staff/{contact}/progress

Progress - Crm Staff

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaCrmStaffController.php:130`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: new, contacted, interested, converted, closed |
| next_follow_up_at | date | Optional | nullable; date |
| note | string | Optional | nullable; string; max:3000 |

Example payload (IDs must be replaced):
```json
{
  "status": "new"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCrmStaffController.php:132`
```php
[
            'status' => ['required', Rule::in(self::STATUSES)],
            'next_follow_up_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]
```

## GET /businesses/{business}/crm/create

Read create form options - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:143`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.contact`, `data.assignees`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/crm/export

Export - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:225`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## POST /businesses/{business}/crm/import

Import - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:252`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| file | file | Required | required; file; mimes:csv,txt; max:2048 |

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessCrmController.php:254`
```php
['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]
```

## GET /businesses/{business}/crm/{contact}

Read detail - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:164`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

Domain response data keys: `data.business`, `data.customer`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/crm/{contact}

Update - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:184`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:150 |
| email | string | Optional | nullable; email; max:190; Unique within the server rule scope |
| phone | string | Optional | nullable; string; max:30 |
| company | string | Optional | nullable; string; max:190 |
| status | string | Required | required; Allowed: new, contacted, interested, converted, closed |
| source | string | Required | required; Allowed: manual, import, product_enquiry, service_enquiry, wishlist, chat, other |
| tags | string | Optional | nullable; string; max:500 |
| next_follow_up_at | date | Optional | nullable; date |
| notes | string | Optional | nullable; string; max:10000 |
| assigned_to | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "status": "new",
  "source": "manual"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessCrmController.php:46`
```php
[
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                // Email is unique per business (same rule the CSV import follows).
                Rule::unique((new GrowyaCrmContact)->getTable(), 'email')
                    ->where('business_id', $business->id)->ignore($ignoreId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:190'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'source' => ['required', Rule::in(self::SOURCES)],
            'tags' => ['nullable', 'string', 'max:500'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('business_users', 'user_id')
                ->where('business_id', $business->id)->where('is_active', true)],
        ]
```

## GET /businesses/{business}/crm/{contact}/360

Read detail - Crm Advanced

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaCrmAdvancedController.php:24`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

Domain response data keys: `data.business`, `data.contact`, `data.orders`, `data.wish`, `data.chat`, `data.productEnquiries`, `data.serviceEnquiries`, `data.links`, `data.activities`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/crm/{contact}/activities

Note - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: customers.manage
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:212`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| type | string | Required | required; Allowed: note, call, meeting, follow_up |
| description | string | Required | required; string; max:5000 |

Example payload (IDs must be replaced):
```json
{
  "type": "note",
  "description": "Demo text"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessCrmController.php:215`
```php
[
            'type' => ['required', Rule::in(['note', 'call', 'meeting', 'follow_up'])],
            'description' => ['required', 'string', 'max:5000'],
        ]
```

## GET /businesses/{business}/crm/{contact}/edit

Read edit form options - Business Crm

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: customers.view
- Source: `app/Http/Controllers/GrowyaBusinessCrmController.php:177`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `contact`

Domain response data keys: `data.business`, `data.contact`, `data.assignees`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/lead-insights

Owner - Lead Insights

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: leads.view
- Source: `app/Http/Controllers/GrowyaLeadInsightsController.php:116`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |
| source | string | Optional | nullable; in:all,product_enquiry,service_enquiry,wishlist,chat; Allowed: all, product_enquiry, service_enquiry, wishlist, chat |
| status | string | Optional | nullable; in:all,new,contacted,follow_up,converted,closed; Allowed: all, new, contacted, follow_up, converted, closed |

Response: JSON object; see the native account/permissions contract or original controller payload.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaLeadInsightsController.php:16`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:all,product_enquiry,service_enquiry,wishlist,chat'],
            'status' => ['nullable', 'in:all,new,contacted,follow_up,converted,closed'],
        ]
```

## GET /businesses/{business}/lead-insights/export

Owner export - Lead Insights

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: leads.view
- Source: `app/Http/Controllers/GrowyaLeadInsightsController.php:154`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |
| source | string | Optional | nullable; in:all,product_enquiry,service_enquiry,wishlist,chat; Allowed: all, product_enquiry, service_enquiry, wishlist, chat |
| status | string | Optional | nullable; in:all,new,contacted,follow_up,converted,closed; Allowed: all, new, contacted, follow_up, converted, closed |

Response: JSON object; see the native account/permissions contract or original controller payload.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaLeadInsightsController.php:16`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:all,product_enquiry,service_enquiry,wishlist,chat'],
            'status' => ['nullable', 'in:all,new,contacted,follow_up,converted,closed'],
        ]
```

## GET /businesses/{business}/product-enquiries

List - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: product_enquiries.view
- Source: `app/Http/Controllers/ProductEnquiryController.php:80`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| search | string | Optional | nullable; string; max:120 |
| status | string | Optional | nullable; Allowed: new, contacted, converted, closed |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.enquiries`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/ProductEnquiryController.php:82`
```php
[
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['new', 'contacted', 'converted', 'closed'])],
        ]
```

## GET /businesses/{business}/product-enquiries/{enquiry}

Read detail - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: product_enquiries.view
- Source: `app/Http/Controllers/ProductEnquiryController.php:102`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`

Domain response data keys: `data.business`, `data.enquiry`, `data.notes`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/product-enquiries/{enquiry}/notes

Note - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: product_enquiries.manage
- Source: `app/Http/Controllers/ProductEnquiryController.php:122`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| note | string | Required | required; string; max:10000 |
| follow_up_at | string | Optional | nullable; date_format:Y-m-d\TH:i; after:now |

Example payload (IDs must be replaced):
```json
{
  "note": "Demo text"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductEnquiryController.php:125`
```php
['note' => ['required', 'string', 'max:10000'], 'follow_up_at' => ['nullable', 'date_format:Y-m-d\\TH:i', 'after:now']]
```

## PATCH /businesses/{business}/product-enquiries/{enquiry}/notes/{note}/complete

Complete - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: product_enquiries.manage
- Source: `app/Http/Controllers/ProductEnquiryController.php:137`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`, `note`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /businesses/{business}/product-enquiries/{enquiry}/status

Status - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: product_enquiries.manage
- Source: `app/Http/Controllers/ProductEnquiryController.php:112`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: new, contacted, converted, closed |

Example payload (IDs must be replaced):
```json
{
  "status": "new"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductEnquiryController.php:115`
```php
['status' => ['required', Rule::in(['new', 'contacted', 'converted', 'closed'])]]
```

## GET /businesses/{business}/service-enquiries

List - Service Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: service_enquiries.view
- Source: `app/Http/Controllers/ServiceEnquiryController.php:53`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| date_from | string | Optional | date_format:Y-m-d |
| date_to | string | Optional | date_format:Y-m-d |

Additional read/query inputs: `search`, `status`, `service_id`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.enquiries`, `data.services`, `data.counts`, `data.total`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/ServiceEnquiryController.php:110`
```php
[
                'date_from' => ['date_format:Y-m-d'],
            ]
```

`app/Http/Controllers/ServiceEnquiryController.php:122`
```php
[
                'date_to' => ['date_format:Y-m-d'],
            ]
```

## GET /businesses/{business}/service-enquiries/{enquiry}

Read detail - Service Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: service_enquiries.view
- Source: `app/Http/Controllers/ServiceEnquiryController.php:162`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`

Domain response data keys: `data.business`, `data.enquiry`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/service-enquiries/{enquiry}/follow-ups

Create or save - Service Enquiry Follow Up

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: service_enquiries.manage
- Source: `app/Http/Controllers/ServiceEnquiryFollowUpController.php:135`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| note | string | Required | required; string; max:10000 |
| follow_up_at | string | Optional | nullable; date_format:Y-m-d\TH:i |

Example payload (IDs must be replaced):
```json
{
  "note": "Demo text"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ServiceEnquiryFollowUpController.php:143`
```php
[
            'note' => [
                'required',
                'string',
                'max:10000',
            ],

            'follow_up_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
        ]
```

## PATCH /businesses/{business}/service-enquiries/{enquiry}/follow-ups/{followUp}/complete

Complete - Service Enquiry Follow Up

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: service_enquiries.manage
- Source: `app/Http/Controllers/ServiceEnquiryFollowUpController.php:197`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`, `followUp`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /businesses/{business}/service-enquiries/{enquiry}/status

Update status - Service Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: service_enquiries.manage
- Source: `app/Http/Controllers/ServiceEnquiryController.php:193`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `enquiry`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: new, contacted, in_progress, converted, closed |

Example payload (IDs must be replaced):
```json
{
  "status": "new"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ServiceEnquiryController.php:204`
```php
[
            'status' => [
                'required',
                Rule::in([
                    'new',
                    'contacted',
                    'in_progress',
                    'converted',
                    'closed',
                ]),
            ],
        ]
```

## GET /businesses/{business}/service-follow-ups

List - Service Enquiry Follow Up

- Group: 11 Leads CRM and staff performance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: service_enquiries.view
- Source: `app/Http/Controllers/ServiceEnquiryFollowUpController.php:45`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `filter`, `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.followUps`, `data.counts`, `data.filter`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /products/{business}/{product}/enquiry

Read create form options - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/ProductEnquiryController.php:34`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `product`

Domain response data keys: `data.business`, `data.product`.

## POST /products/{business}/{product}/enquiry

Create or save - Product Enquiry

- Group: 11 Leads CRM and staff performance
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/ProductEnquiryController.php:41`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `business`, `product`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:255 |
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |
| quantity | integer | Required | required; integer; min:1; max:100000 |
| message | string | Optional | nullable; string; max:3000 |
| website | string | Optional | nullable; size:0 |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "phone": "9876543210",
  "quantity": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/ProductEnquiryController.php:43`
```php
[
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'message' => ['nullable', 'string', 'max:3000'],
            'website' => ['nullable', 'size:0'],
        ]
```

## GET /admin/business-reviews

List - Admin Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AdminBusinessReviewController.php:16`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; Allowed: pending, approved, rejected, all |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.reviews`, `data.status`, `data.counts`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/AdminBusinessReviewController.php:19`
```php
[
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
        ]
```

## PATCH /admin/business-reviews/{review}/moderate

Update - Admin Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/AdminBusinessReviewController.php:50`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `review`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: approved, rejected, pending |

Example payload (IDs must be replaced):
```json
{
  "status": "approved"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/AdminBusinessReviewController.php:53`
```php
[
            'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
        ]
```

## GET /admin/referrals

Admin - Referral

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaReferralController.php:26`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.referrals`, `data.points`, `data.users`, `data.businesses`, `data.settings`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/referrals/settings

Settings - Referral

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaReferralController.php:36`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| customer_points | integer | Required | required; integer; between:0,100000 |
| business_points | integer | Required | required; integer; between:0,100000 |
| enabled | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "customer_points": 1,
  "business_points": 1,
  "enabled": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaReferralController.php:38`
```php
['customer_points' => ['required', 'integer', 'between:0,100000'], 'business_points' => ['required', 'integer', 'between:0,100000'], 'enabled' => ['required', 'boolean']]
```

## POST /admin/referrals/{referral}/approve

Approve business reward - Referral

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaReferralController.php:45`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `referral`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /admin/wishlist-interests

Admin - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaWishlistController.php:116`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.rows`, `data.products`, `data.services`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/wishlist-interests/{interest}

Admin update - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaWishlistController.php:134`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `interest`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: new, contacted, follow_up, converted, closed |
| seller_note | string | Optional | nullable; string; max:3000 |
| follow_up_at | date | Optional | nullable; date |

Example payload (IDs must be replaced):
```json
{
  "status": "new"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaWishlistController.php:142`
```php
[
            'status' => ['required', Rule::in(['new', 'contacted', 'follow_up', 'converted', 'closed'])],
            'seller_note' => ['nullable', 'string', 'max:3000'],
            'follow_up_at' => ['nullable', 'date'],
        ]
```

## GET /b/{slug}/reviews

Public index - Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessReviewController.php:22`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `slug`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.reviews`, `data.summary`, `data.myReview`.

## POST /b/{slug}/reviews

Create or save - Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessReviewController.php:36`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `slug`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| rating | integer | Required | required; integer; between:1,5 |
| review | string | Required | required; string; min:5; max:3000 |

Example payload (IDs must be replaced):
```json
{
  "rating": 1,
  "review": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessReviewController.php:40`
```php
[
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['required', 'string', 'min:5', 'max:3000'],
        ]
```

## PUT /b/{slug}/reviews/{review}

Update - Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessReviewController.php:64`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `slug`, `review`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| rating | integer | Required | required; integer; between:1,5 |
| review | string | Required | required; string; min:5; max:3000 |

Example payload (IDs must be replaced):
```json
{
  "rating": 1,
  "review": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessReviewController.php:68`
```php
[
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['required', 'string', 'min:5', 'max:3000'],
        ]
```

## DELETE /businesses/{business}/follow

Delete or remove - Business Follow

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessFollowController.php:61`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## POST /businesses/{business}/follow

Create or save - Business Follow

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessFollowController.php:28`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /businesses/{business}/reviews

Owner index - Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: reviews.view
- Source: `app/Http/Controllers/BusinessReviewController.php:78`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.reviews`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/reviews/{review}/reply

Reply - Business Review

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: reviews.manage
- Source: `app/Http/Controllers/BusinessReviewController.php:87`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `review`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| business_reply | string | Required | required; string; min:2; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "business_reply": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/BusinessReviewController.php:90`
```php
['business_reply' => ['required', 'string', 'min:2', 'max:2000']]
```

## GET /businesses/{business}/wishlist-interests

Seller - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: wishlist.view
- Source: `app/Http/Controllers/GrowyaWishlistController.php:102`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `status`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.rows`, `data.products`, `data.services`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/wishlist-interests/{interest}

Update - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: wishlist.manage
- Source: `app/Http/Controllers/GrowyaWishlistController.php:126`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `interest`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: new, contacted, follow_up, converted, closed |
| seller_note | string | Optional | nullable; string; max:3000 |
| follow_up_at | date | Optional | nullable; date |

Example payload (IDs must be replaced):
```json
{
  "status": "new"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaWishlistController.php:142`
```php
[
            'status' => ['required', Rule::in(['new', 'contacted', 'follow_up', 'converted', 'closed'])],
            'seller_note' => ['nullable', 'string', 'max:3000'],
            'follow_up_at' => ['nullable', 'date'],
        ]
```

## GET /my-following

List - Business Follow

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/BusinessFollowController.php:14`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.following`.

## GET /my-referrals

Read own records - Referral

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaReferralController.php:13`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.code`, `data.records`, `data.points`, `data.activity`.

## GET /my-wishlist

Read own records - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaWishlistController.php:29`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.products`, `data.services`, `data.serviceModels`, `data.interests`.

## DELETE /my-wishlist/{type}/{id}

Remove - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaWishlistController.php:79`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `type`, `id`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## POST /my-wishlist/{type}/{id}

Add - Wishlist

- Group: 12 Follows wishlist reviews and referrals
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaWishlistController.php:40`
- Rate limits: 120 requests / 1 minute(s), 15 requests / 1 minute(s)
- Path inputs: `type`, `id`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| contact_consent | boolean | Optional | nullable; boolean |

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaWishlistController.php:43`
```php
['contact_consent' => ['nullable', 'boolean']]
```

## GET /admin/chat-reports

Reports - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:120`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `status`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.reports`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/chat-reports/{report}

Review - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:128`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `report`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: reviewed, dismissed |
| admin_note | string | Optional | nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "status": "reviewed"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatEnhancementController.php:130`
```php
['status' => ['required', Rule::in(['reviewed', 'dismissed'])], 'admin_note' => ['nullable', 'string', 'max:2000']]
```

## GET /businesses/{business}/messages

Inbox - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: chats.view
- Source: `app/Http/Controllers/GrowyaChatController.php:50`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.threads`, `data.heading`, `data.business`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

## POST /businesses/{business}/messages/start

Start - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatController.php:61`
- Rate limits: 120 requests / 1 minute(s), 15 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| context_type | string | Required | required; Allowed: general, product, service |
| context_id | integer | Optional | nullable; integer; min:1 |

Example payload (IDs must be replaced):
```json
{
  "context_type": "general"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

- context_type is general/product/service. context_id is required for a product/service and must be active inside this business. An owner cannot start a customer conversation with their own business.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatController.php:64`
```php
[
            'context_type' => ['required', Rule::in(['general','product','service'])],
            'context_id' => ['nullable','integer','min:1'],
        ]
```

## GET /messages/{thread}

Read detail - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatController.php:85`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `thread`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.thread`, `data.messages`.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

## POST /messages/{thread}

Send - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatController.php:111`
- Rate limits: 120 requests / 1 minute(s), 20 requests / 1 minute(s)
- Path inputs: `thread`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| body | string | Conditional | nullable; string; max:5000; required_without:attachment |
| attachment | file | Conditional | nullable; file; mimes:jpg,jpeg,png,webp,pdf; max:5120; required_without:body |

Example payload (IDs must be replaced):
```json
{
  "body": "Is this product available?"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

- Supply body or attachment, at least one. attachment supports JPG/JPEG/PNG/WEBP/PDF up to 5120 KB. The conversation must not be blocked.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatController.php:115`
```php
[
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,webp,pdf','max:5120','required_without:body'],
        ]
```

## GET /messages/{thread}/activity

Activity - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:152`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `thread`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.thread`, `data.logs`.

## PATCH /messages/{thread}/assign

Assign - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:61`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `thread`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| assigned_to | integer | Optional | nullable; integer; exists:users,id |

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatEnhancementController.php:63`
```php
['assigned_to' => ['nullable', 'integer', 'exists:users,id']]
```

## PATCH /messages/{thread}/block

Block - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:86`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `thread`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| blocked | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "blocked": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatEnhancementController.php:90`
```php
['blocked' => ['required', 'boolean']]
```

## GET /messages/{thread}/files/{message}

Download attachment - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatController.php:152`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `thread`, `message`

Response: binary/stream download.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## PATCH /messages/{thread}/followup

Followup - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:140`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `thread`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| followup_status | string | Required | required; Allowed: new, contacted, followup, converted, closed |
| followup_at | date | Optional | nullable; date |
| note | string | Optional | nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "followup_status": "new"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatEnhancementController.php:142`
```php
['followup_status' => ['required', Rule::in(['new', 'contacted', 'followup', 'converted', 'closed'])], 'followup_at' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:2000']]
```

## GET /messages/{thread}/poll

Poll updates - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatController.php:97`
- Rate limits: 120 requests / 1 minute(s), 12 requests / 1 minute(s)
- Path inputs: `thread`

Additional read/query inputs: `after`. These are read by the controller; not all have explicit validator limits.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

- Query after is the last seen message ID (default 0). Response is direct JSON messages, not data.messages. Polling also updates read markers.

Exact explicit JSON expression:
```php
json(['messages' => $messages->map(fn ($m) => [
            'id' => $m->id, 'mine' => (int) $m->sender_id === (int) auth()->id(),
            'body' => $m->body, 'attachment' => $m->file_path ? route('growya.chat.file', [$thread, $m]) : null,
            'time' => $m->created_at->format('d M, H:i'),
        ])])
```

## POST /messages/{thread}/report

Report - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:101`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `thread`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| reason | string | Required | required; string; min:10; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaChatEnhancementController.php:103`
```php
['reason' => ['required', 'string', 'min:10', 'max:2000']]
```

## GET /my-messages

Read own records - Chat

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatController.php:41`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `search`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.threads`, `data.heading`, `data.business`.

- Customer/owner/staff participant and assigned-thread checks apply. Being logged in or knowing a thread ID is not sufficient.

## GET /my-messages/unread-count

Unread - Chat Enhancement

- Group: 13 Chat and conversation moderation
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaChatEnhancementController.php:160`
- Rate limits: 120 requests / 1 minute(s), 15 requests / 1 minute(s)

Exact explicit JSON expression:
```php
json(['customer_threads' => $customer, 'owner_threads' => $seller])
```

## GET /my-notifications

List - Notification

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaNotificationController.php:17`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `filter`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.items`.

## GET /my-notifications/poll

Poll updates - Live Notification

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaLiveNotificationController.php:11`
- Rate limits: 120 requests / 1 minute(s), 12 requests / 1 minute(s)

- Response is direct JSON: count, latest_id and items. created_at here is human-readable relative text, not an ISO timestamp. open_url is a web POST action URL; invoke the API open action for its recipient ID.

Exact explicit JSON expression:
```php
json([
            'count' => $count,
            'latest_id' => (int) ($items->first()?->id ?? 0),
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->notification?->title ?? 'Notification',
                'message' => $item->notification?->message ?? '',
                'created_at' => $item->created_at?->diffForHumans(),
                'open_url' => route('growya.notifications.open', $item),
            ])->values(),
        ])
```

## PATCH /my-notifications/read-all

Read all - Notification

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaNotificationController.php:53`
- Rate limits: 120 requests / 1 minute(s)

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## POST /my-notifications/{recipient}/open

Open - Notification

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaNotificationController.php:27`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `recipient`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /my-notifications/{recipient}/read

Read - Notification

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaNotificationController.php:44`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `recipient`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /my-reminder-preferences

Read edit form options - Reminder Preference

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaReminderPreferenceController.php:8`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.settings`.

## PATCH /my-reminder-preferences

Update - Reminder Preference

- Group: 14 Notifications and reminders
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaReminderPreferenceController.php:12`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| wishlist_reminders | boolean | Required | required; boolean |
| chat_reminders | boolean | Required | required; boolean |
| subscription_reminders | boolean | Required | required; boolean |
| promotion_reminders | boolean | Required | required; boolean |
| support_reminders | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "wishlist_reminders": false,
  "chat_reminders": false,
  "subscription_reminders": false,
  "promotion_reminders": false,
  "support_reminders": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /support/tickets

List - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:42`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | Allowed: open, in_progress, resolved, closed |
| search | string | Optional | string; max:100 |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.tickets`, `data.admin`, `data.support`.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

Exact validation blocks:

`app/Http/Controllers/GrowyaSupportTicketController.php:56`
```php
['status' => Rule::in(['open', 'in_progress', 'resolved', 'closed'])]
```

`app/Http/Controllers/GrowyaSupportTicketController.php:60`
```php
['search' => 'string|max:100']
```

## POST /support/tickets

Create or save - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:72`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| subject | string | Required | required; string; max:200 |
| category | string | Required | required; Allowed: orders, payments, refunds, business, technical, other |
| priority | string | Required | required; Allowed: low, medium, high, urgent |
| message | string | Required | required; string; min:5; max:10000 |
| business_id | integer | Optional | nullable; integer; exists:businesses,id |
| attachments | array | Optional | nullable; array; max:3 |
| attachments.* | file | Optional | file; mimes:jpg,jpeg,png,webp,pdf; max:5120 |

Example payload (IDs must be replaced):
```json
{
  "subject": "demo",
  "category": "orders",
  "priority": "low",
  "message": "Demo text"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaSupportTicketController.php:73`
```php
['subject' => 'required|string|max:200', 'category' => ['required', Rule::in(['orders', 'payments', 'refunds', 'business', 'technical', 'other'])], 'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])], 'message' => 'required|string|min:5|max:10000', 'business_id' => 'nullable|integer|exists:businesses,id', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120']
```

## GET /support/tickets/create

Read create form options - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:67`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.businesses`.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

## GET /support/tickets/{ticket}

Read detail - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:88`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `ticket`

Domain response data keys: `data.ticket`, `data.admin`, `data.support`, `data.agents`.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

## PATCH /support/tickets/{ticket}/assign

Assign - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:177`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `ticket`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| assigned_to | integer | Optional | nullable; integer; exists:users,id |

Response: `message` and web `location`; no guaranteed resource object.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaSupportTicketController.php:179`
```php
['assigned_to' => 'nullable|integer|exists:users,id']
```

## GET /support/tickets/{ticket}/attachments/{attachment}

Attachment - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:202`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `ticket`, `attachment`

Response: binary/stream download.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## POST /support/tickets/{ticket}/reply

Reply - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:107`
- Rate limits: 120 requests / 1 minute(s), 15 requests / 1 minute(s)
- Path inputs: `ticket`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| message | string | Required | required; string; min:1; max:10000 |
| is_internal | boolean | Optional | sometimes; boolean |
| attachments | array | Optional | nullable; array; max:3 |
| attachments.* | file | Optional | file; mimes:jpg,jpeg,png,webp,pdf; max:5120 |

Example payload (IDs must be replaced):
```json
{
  "message": "Demo text"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaSupportTicketController.php:111`
```php
['message' => 'required|string|min:1|max:10000', 'is_internal' => 'sometimes|boolean', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120']
```

## PATCH /support/tickets/{ticket}/status

Update - Support Ticket

- Group: 15 Support tickets
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSupportTicketController.php:138`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `ticket`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: open, in_progress, resolved, closed |
| priority | string | Required | required; Allowed: low, medium, high, urgent |

Example payload (IDs must be replaced):
```json
{
  "status": "open",
  "priority": "low"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Only the ticket creator or permitted platform support can read/reply. Internal notes are restricted; assign/update controls have additional platform-support checks.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaSupportTicketController.php:141`
```php
['status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])], 'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])]]
```

## GET /admin/business-kyc

Admin - Business Kyc

- Group: 16 KYC documents
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessKycController.php:155`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Optional | nullable; Allowed: pending, approved, rejected, all |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.submissions`, `data.status`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Upload documents using named keys: documents[gst_certificate], documents[pan], documents[business_registration], documents[owner_identity], documents[address_proof]. Numeric documents[] keys are rejected. Submit 1 to 5 files; each PDF/JPG/JPEG/PNG/WEBP up to 5120 KB. Read the remaining fields and review-state constraints for this endpoint.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessKycController.php:157`
```php
['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])]]
```

## GET /admin/business-kyc/{submission}

Admin show - Business Kyc

- Group: 16 KYC documents
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessKycController.php:168`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `submission`

Domain response data keys: `data.submission`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Upload documents using named keys: documents[gst_certificate], documents[pan], documents[business_registration], documents[owner_identity], documents[address_proof]. Numeric documents[] keys are rejected. Submit 1 to 5 files; each PDF/JPG/JPEG/PNG/WEBP up to 5120 KB. Read the remaining fields and review-state constraints for this endpoint.

## PATCH /admin/business-kyc/{submission}

Review - Business Kyc

- Group: 16 KYC documents
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessKycController.php:176`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `submission`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; Allowed: approved, rejected |
| reason | string | Conditional | required_if:decision,rejected; nullable; string; min:5; max:2000 |
| reverify_by | date | Optional | nullable; date; after:today |

Example payload (IDs must be replaced):
```json
{
  "decision": "approved",
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Upload documents using named keys: documents[gst_certificate], documents[pan], documents[business_registration], documents[owner_identity], documents[address_proof]. Numeric documents[] keys are rejected. Submit 1 to 5 files; each PDF/JPG/JPEG/PNG/WEBP up to 5120 KB. Read the remaining fields and review-state constraints for this endpoint.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessKycController.php:178`
```php
[
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'min:5', 'max:2000'],
            'reverify_by' => ['nullable', 'date', 'after:today'],
        ]
```

## GET /business-kyc/{submission}/documents/{document}

Download - Business Kyc

- Group: 16 KYC documents
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessKycController.php:213`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `submission`, `document`

Response: binary/stream download.

- Upload documents using named keys: documents[gst_certificate], documents[pan], documents[business_registration], documents[owner_identity], documents[address_proof]. Numeric documents[] keys are rejected. Submit 1 to 5 files; each PDF/JPG/JPEG/PNG/WEBP up to 5120 KB. Read the remaining fields and review-state constraints for this endpoint.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses/{business}/kyc

Owner - Business Kyc

- Group: 16 KYC documents
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: documents.view
- Source: `app/Http/Controllers/GrowyaBusinessKycController.php:42`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.submissions`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Upload documents using named keys: documents[gst_certificate], documents[pan], documents[business_registration], documents[owner_identity], documents[address_proof]. Numeric documents[] keys are rejected. Submit 1 to 5 files; each PDF/JPG/JPEG/PNG/WEBP up to 5120 KB. Read the remaining fields and review-state constraints for this endpoint.

## POST /businesses/{business}/kyc

Submit - Business Kyc

- Group: 16 KYC documents
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: documents.manage
- Source: `app/Http/Controllers/GrowyaBusinessKycController.php:50`
- Rate limits: 120 requests / 1 minute(s), 3 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| legal_name | string | Required | required; string; max:255 |
| entity_type | string | Required | required; Allowed: proprietorship, partnership, llp, private_limited, other |
| documents | array | Required | required; array; min:1; max:5 |
| documents.* | file | Required | required; file; mimes:pdf,jpg,jpeg,png,webp; max:5120 |
| expires_on | array | Optional | nullable; array |
| expires_on.* | date | Optional | nullable; date; after:today |
| declaration | string | Optional | accepted |

Example payload (IDs must be replaced):
```json
{
  "legal_name": "demo",
  "entity_type": "proprietorship",
  "documents": []
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Upload documents using named keys: documents[gst_certificate], documents[pan], documents[business_registration], documents[owner_identity], documents[address_proof]. Numeric documents[] keys are rejected. Submit 1 to 5 files; each PDF/JPG/JPEG/PNG/WEBP up to 5120 KB. Read the remaining fields and review-state constraints for this endpoint.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessKycController.php:52`
```php
[
            'legal_name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', Rule::in(['proprietorship', 'partnership', 'llp', 'private_limited', 'other'])],
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'expires_on' => ['nullable', 'array'],
            'expires_on.*' => ['nullable', 'date', 'after:today'],
            'declaration' => ['accepted'],
        ]
```

## GET /admin/businesses/{business}/plan-overrides

Read edit form options - Business Plan Override

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessPlanOverrideController.php:17`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.overrides`, `data.audits`, `data.usage`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /admin/businesses/{business}/plan-overrides

Save - Business Plan Override

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessPlanOverrideController.php:25`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| feature_key | string | Required | required |
| value | integer | Required | required; integer; min:-1; max:1000000 |
| reason | string | Required | required; string; min:5; max:1000 |
| expires_at | date | Optional | nullable; date; after:now |

Example payload (IDs must be replaced):
```json
{
  "feature_key": "demo",
  "value": 1,
  "reason": "demo"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessPlanOverrideController.php:27`
```php
[
            'feature_key' => ['required', Rule::in(array_keys(GrowyaPlanLimitService::KEYS))],
            'value' => ['required', 'integer', 'min:-1', 'max:1000000'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]
```

## DELETE /admin/businesses/{business}/plan-overrides/{key}

Remove - Business Plan Override

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaBusinessPlanOverrideController.php:65`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `key`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| reason | string | Required | required; string; min:5; max:1000 |

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaBusinessPlanOverrideController.php:68`
```php
['reason' => ['required', 'string', 'min:5', 'max:1000']]
```

## GET /admin/subscription-billing

Admin index - Subscription Billing

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionBillingController.php:33`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | date | Optional | nullable; date |
| to | date | Optional | nullable; date; after_or_equal:from |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.docs`, `data.unissued`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaSubscriptionBillingController.php:68`
```php
[
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]
```

## GET /admin/subscription-billing/export

Export - Subscription Billing

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionBillingController.php:108`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | date | Optional | nullable; date |
| to | date | Optional | nullable; date; after_or_equal:from |

Response: binary/stream download.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

Exact validation blocks:

`app/Http/Controllers/GrowyaSubscriptionBillingController.php:68`
```php
[
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]
```

## POST /admin/subscription-billing/payments/{payment}

Issue - Subscription Billing

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionBillingController.php:80`
- Rate limits: 120 requests / 1 minute(s), 10 requests / 1 minute(s)
- Path inputs: `payment`

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /admin/subscription-plans

List - Plan

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPlanController.php:12`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.plans`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /admin/subscription-plans

Create or save - Plan

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPlanController.php:17`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:120 |
| slug | string | Required | required; max:120; unique:plans,slug |
| monthly_price | number | Required | required; numeric; min:0; max:99999999 |
| yearly_price | number | Required | required; numeric; min:0; max:99999999 |
| trial_days | integer | Required | required; integer; min:0; max:365 |
| sort_order | integer | Optional | nullable; integer; min:0; max:9999 |
| is_active | boolean | Optional | nullable; boolean |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "slug": "demo",
  "monthly_price": 100,
  "yearly_price": 100,
  "trial_days": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPlanController.php:19`
```php
[
            'name'=>['required','string','max:120'],
            'slug'=>['required','alpha_dash','max:120','unique:plans,slug'],
            'monthly_price'=>['required','numeric','min:0','max:99999999'],
            'yearly_price'=>['required','numeric','min:0','max:99999999'],
            'trial_days'=>['required','integer','min:0','max:365'],
            'sort_order'=>['nullable','integer','min:0','max:9999'],
            'is_active'=>['nullable','boolean'],
        ]
```

## PATCH /admin/subscription-plans/{plan}

Update - Plan

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPlanController.php:35`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `plan`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:120 |
| monthly_price | number | Required | required; numeric; min:0; max:99999999 |
| yearly_price | number | Required | required; numeric; min:0; max:99999999 |
| trial_days | integer | Required | required; integer; min:0; max:365 |
| sort_order | integer | Optional | nullable; integer; min:0; max:9999 |
| is_active | boolean | Optional | nullable; boolean |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "monthly_price": 100,
  "yearly_price": 100,
  "trial_days": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPlanController.php:37`
```php
[
            'name'=>['required','string','max:120'],
            'monthly_price'=>['required','numeric','min:0','max:99999999'],
            'yearly_price'=>['required','numeric','min:0','max:99999999'],
            'trial_days'=>['required','integer','min:0','max:365'],
            'sort_order'=>['nullable','integer','min:0','max:9999'],
            'is_active'=>['nullable','boolean'],
        ]
```

## GET /admin/subscription-plans/{plan}/limits

Read edit form options - Plan Entitlement

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPlanEntitlementController.php:17`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `plan`

Domain response data keys: `data.plan`, `data.existing`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/subscription-plans/{plan}/limits

Update - Plan Entitlement

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPlanEntitlementController.php:26`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `plan`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| max_products | integer | Required | required; integer; min:-1; max:1000000 |
| max_services | integer | Required | required; integer; min:-1; max:1000000 |
| max_staff | integer | Required | required; integer; min:-1; max:1000000 |
| max_gallery | integer | Required | required; integer; min:-1; max:1000000 |
| featured_listing | string | Required | required; in:0,1; Allowed: 0, 1 |

Example payload (IDs must be replaced):
```json
{
  "max_products": 1,
  "max_services": 1,
  "max_staff": 1,
  "max_gallery": 1,
  "featured_listing": "0"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /admin/subscriptions

Admin index - Subscription

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionController.php:72`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `status`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.subscriptions`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- A request does not activate a plan. Super admin approval/payment recording is separate. Billing cycles and plan availability are validated server side.

## PATCH /admin/subscriptions/{subscription}/review

Review - Subscription

- Group: 17 Plans subscriptions and billing
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionController.php:89`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `subscription`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; Allowed: approve, reject |
| payment_method | string | Optional | nullable; Allowed: cash, upi, bank_transfer, complimentary |
| transaction_id | id | Optional | nullable; string; max:150 |
| note | string | Optional | nullable; string; max:500 |

Example payload (IDs must be replaced):
```json
{
  "decision": "approve"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- A request does not activate a plan. Super admin approval/payment recording is separate. Billing cycles and plan availability are validated server side.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaSubscriptionController.php:91`
```php
[
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'upi', 'bank_transfer', 'complimentary'])],
            'transaction_id' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
        ]
```

## GET /businesses/{business}/billing

Owner index - Subscription Billing

- Group: 17 Plans subscriptions and billing
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: billing.view
- Source: `app/Http/Controllers/GrowyaSubscriptionBillingController.php:52`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | date | Optional | nullable; date |
| to | date | Optional | nullable; date; after_or_equal:from |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.docs`, `data.business`, `data.unissued`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaSubscriptionBillingController.php:68`
```php
[
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]
```

## GET /businesses/{business}/plan-usage

Usage - Plan Entitlement

- Group: 17 Plans subscriptions and billing
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: subscriptions.view
- Source: `app/Http/Controllers/GrowyaPlanEntitlementController.php:45`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.active`, `data.usage`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/subscriptions

List - Subscription

- Group: 17 Plans subscriptions and billing
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: subscriptions.view
- Source: `app/Http/Controllers/GrowyaSubscriptionController.php:28`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.plans`, `data.history`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- A request does not activate a plan. Super admin approval/payment recording is separate. Billing cycles and plan availability are validated server side.

## POST /businesses/{business}/subscriptions

Submit request - Subscription

- Group: 17 Plans subscriptions and billing
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: subscriptions.request
- Source: `app/Http/Controllers/GrowyaSubscriptionController.php:37`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| plan_id | integer | Required | required; integer; exists:plans,id |
| billing_cycle | string | Required | required; Allowed: monthly, yearly |

Example payload (IDs must be replaced):
```json
{
  "plan_id": 1,
  "billing_cycle": "monthly"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- A request does not activate a plan. Super admin approval/payment recording is separate. Billing cycles and plan availability are validated server side.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaSubscriptionController.php:39`
```php
['plan_id' => ['required', 'integer', 'exists:plans,id'], 'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])]]
```

## GET /subscription-billing/{document}

Read detail - Subscription Billing

- Group: 17 Plans subscriptions and billing
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionBillingController.php:88`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `document`

Domain response data keys: `data.doc`.

## GET /subscription-billing/{document}/pdf

Download PDF - Subscription Billing

- Group: 17 Plans subscriptions and billing
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaSubscriptionBillingController.php:95`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `document`

Response: binary/stream download.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /admin/paid-promotion-packages

Packages - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:27`
- Rate limits: 120 requests / 1 minute(s)

Domain response data keys: `data.packages`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## POST /admin/paid-promotion-packages

Store package - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:34`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:120 |
| target_type | string | Required | required; Allowed: business, product |
| duration_days | integer | Required | required; integer; min:1; max:365 |
| price | number | Required | required; numeric; min:0; max:99999999 |
| is_active | boolean | Optional | sometimes; boolean |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "target_type": "business",
  "duration_days": 1,
  "price": 100
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaidPromotionController.php:36`
```php
['name' => 'required|string|max:120', 'target_type' => ['required', Rule::in(['business', 'product'])], 'duration_days' => 'required|integer|min:1|max:365', 'price' => 'required|numeric|min:0|max:99999999', 'is_active' => 'sometimes|boolean']
```

## PATCH /admin/paid-promotion-packages/{package}

Update package - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:44`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `package`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:120 |
| duration_days | integer | Required | required; integer; min:1; max:365 |
| price | number | Required | required; numeric; min:0; max:99999999 |
| is_active | boolean | Optional | sometimes; boolean |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "duration_days": 1,
  "price": 100
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaidPromotionController.php:46`
```php
['name' => 'required|string|max:120', 'duration_days' => 'required|integer|min:1|max:365', 'price' => 'required|numeric|min:0|max:99999999', 'is_active' => 'sometimes|boolean']
```

## GET /admin/paid-promotions

Admin index - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Super admin
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:85`
- Rate limits: 120 requests / 1 minute(s)

Additional read/query inputs: `status`, `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.promotions`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

## PATCH /admin/paid-promotions/{promotion}

Review - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Super admin
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:96`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `promotion`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| decision | string | Required | required; Allowed: approve, reject |
| payment_verified | boolean | Optional | sometimes; boolean |
| review_note | string | Optional | nullable; string; max:2000 |

Example payload (IDs must be replaced):
```json
{
  "decision": "approve"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaidPromotionController.php:98`
```php
['decision' => ['required', Rule::in(['approve', 'reject'])], 'payment_verified' => 'sometimes|boolean', 'review_note' => 'nullable|string|max:2000']
```

## GET /businesses/{business}/coupons

List - Coupon

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: coupons.view
- Source: `app/Http/Controllers/GrowyaCouponController.php:36`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.coupons`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/coupons

Create or save - Coupon

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: coupons.manage
- Source: `app/Http/Controllers/GrowyaCouponController.php:52`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| code | string | Required | required; string; max:40; regex:/^[A-Za-z0-9_-]+$/; Unique within the server rule scope |
| discount_type | string | Required | required; Allowed: fixed, percent |
| discount_value | number | Required | required; numeric; max:99999999 |
| minimum_subtotal | number | Required | required; numeric; min:0; max:99999999 |
| maximum_discount | number | Optional | nullable; numeric; max:99999999 |
| usage_limit | integer | Optional | nullable; integer; min:1; max:10000000 |
| per_user_limit | integer | Required | required; integer; min:1; max:10000 |
| starts_at | date | Optional | nullable; date |
| ends_at | date | Optional | nullable; date; after:starts_at |
| is_active | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "code": "demo",
  "discount_type": "fixed",
  "discount_value": 100,
  "minimum_subtotal": 100,
  "per_user_limit": 1,
  "is_active": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCouponController.php:21`
```php
[
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('growya_coupons', 'code')->where('business_id', $business->id)->ignore($coupon?->id)],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'minimum_subtotal' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'per_user_limit' => ['required', 'integer', 'min:1', 'max:10000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]
```

## GET /businesses/{business}/coupons/create

Read create form options - Coupon

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: coupons.view
- Source: `app/Http/Controllers/GrowyaCouponController.php:44`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Domain response data keys: `data.business`, `data.coupon`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PATCH /businesses/{business}/coupons/{coupon}

Update - Coupon

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: coupons.manage
- Source: `app/Http/Controllers/GrowyaCouponController.php:74`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `coupon`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| code | string | Required | required; string; max:40; regex:/^[A-Za-z0-9_-]+$/; Unique within the server rule scope |
| discount_type | string | Required | required; Allowed: fixed, percent |
| discount_value | number | Required | required; numeric; max:99999999 |
| minimum_subtotal | number | Required | required; numeric; min:0; max:99999999 |
| maximum_discount | number | Optional | nullable; numeric; max:99999999 |
| usage_limit | integer | Optional | nullable; integer; min:1; max:10000000 |
| per_user_limit | integer | Required | required; integer; min:1; max:10000 |
| starts_at | date | Optional | nullable; date |
| ends_at | date | Optional | nullable; date; after:starts_at |
| is_active | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "code": "demo",
  "discount_type": "fixed",
  "discount_value": 100,
  "minimum_subtotal": 100,
  "per_user_limit": 1,
  "is_active": false
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaCouponController.php:21`
```php
[
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('growya_coupons', 'code')->where('business_id', $business->id)->ignore($coupon?->id)],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'minimum_subtotal' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'per_user_limit' => ['required', 'integer', 'min:1', 'max:10000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]
```

## GET /businesses/{business}/coupons/{coupon}/edit

Read edit form options - Coupon

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: coupons.view
- Source: `app/Http/Controllers/GrowyaCouponController.php:66`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `coupon`

Domain response data keys: `data.business`, `data.coupon`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/paid-promotions

Owner index - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: promotions.view
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:54`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.packages`, `data.products`, `data.promotions`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/paid-promotions

Submit request - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: promotions.manage
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:61`
- Rate limits: 120 requests / 1 minute(s), 5 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| package_id | id | Required | required; exists:growya_promotion_packages,id |
| product_id | integer | Optional | nullable; integer |
| payment_reference | string | Optional | nullable; string; max:150 |

Example payload (IDs must be replaced):
```json
{
  "package_id": 1
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaPaidPromotionController.php:63`
```php
['package_id' => 'required|exists:growya_promotion_packages,id', 'product_id' => 'nullable|integer', 'payment_reference' => 'nullable|string|max:150']
```

## GET /promoted/{promotion}

Click - Paid Promotion

- Group: 18 Coupons and paid promotions
- Access: Public
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaPaidPromotionController.php:131`
- Rate limits: 120 requests / 1 minute(s), 60 requests / 1 minute(s)
- Path inputs: `promotion`

Response: `message` and web `location`; no guaranteed resource object.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## GET /admin/analytics

Admin - Analytics

- Group: 19 Dashboards analytics and finance
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaAnalyticsController.php:116`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |

Domain response data keys: `data.from`, `data.to`, `data.business`.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaAnalyticsController.php:18`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]
```

## GET /admin/analytics/export

Admin export - Analytics

- Group: 19 Dashboards analytics and finance
- Access: Super admin
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaAnalyticsController.php:136`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |

Response: JSON object; see the native account/permissions contract or original controller payload.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

Exact validation blocks:

`app/Http/Controllers/GrowyaAnalyticsController.php:18`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]
```

## GET /businesses/{business}/analytics

Owner - Analytics

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: analytics.view
- Source: `app/Http/Controllers/GrowyaAnalyticsController.php:105`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |

Domain response data keys: `data.from`, `data.to`, `data.business`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaAnalyticsController.php:18`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]
```

## GET /businesses/{business}/analytics/export

Owner export - Analytics

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: analytics.view
- Source: `app/Http/Controllers/GrowyaAnalyticsController.php:128`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d |

Response: JSON object; see the native account/permissions contract or original controller payload.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaAnalyticsController.php:18`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]
```

## GET /businesses/{business}/finance

List - Finance

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: finance.view
- Source: `app/Http/Controllers/GrowyaFinanceController.php:73`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d; after_or_equal:from |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Domain response data keys: `data.business`, `data.from`, `data.to`, `data.summary`, `data.categories`, `data.entries`, `data.months`.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/GrowyaFinanceController.php:21`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]
```

## POST /businesses/{business}/finance

Create or save - Finance

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: finance.manage
- Source: `app/Http/Controllers/GrowyaFinanceController.php:104`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| kind | string | Required | required; Allowed: income, expense |
| amount | number | Required | required; numeric; max:999999999999.99 |
| entry_date | string | Required | required; date_format:Y-m-d |
| category_id | integer | Optional | nullable; integer |
| payment_method | string | Required | required; Allowed: cash, upi, bank, card, cheque, other |
| reference | string | Optional | nullable; string; max:140 |
| description | string | Optional | nullable; string; max:500 |

Example payload (IDs must be replaced):
```json
{
  "kind": "income",
  "amount": 100,
  "entry_date": "2026-10-20",
  "payment_method": "cash"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaFinanceController.php:106`
```php
[
            'kind' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'integer'],
            'payment_method' => ['required', Rule::in(['cash', 'upi', 'bank', 'card', 'cheque', 'other'])],
            'reference' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:500'],
        ]
```

## POST /businesses/{business}/finance/categories

Store category - Finance

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: finance.manage
- Source: `app/Http/Controllers/GrowyaFinanceController.php:91`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| name | string | Required | required; string; max:100 |
| kind | string | Required | required; Allowed: income, expense |

Example payload (IDs must be replaced):
```json
{
  "name": "Demo item",
  "kind": "income"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaFinanceController.php:93`
```php
['name' => ['required', 'string', 'max:100'], 'kind' => ['required', Rule::in(['income', 'expense'])]]
```

## GET /businesses/{business}/finance/export

Export - Finance

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: finance.view
- Source: `app/Http/Controllers/GrowyaFinanceController.php:170`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d; after_or_equal:from |

Response: binary/stream download.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

Exact validation blocks:

`app/Http/Controllers/GrowyaFinanceController.php:21`
```php
[
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]
```

## DELETE /businesses/{business}/finance/{entry}

Delete or remove - Finance

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: finance.manage
- Source: `app/Http/Controllers/GrowyaFinanceController.php:159`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `entry`

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

## PATCH /businesses/{business}/finance/{entry}

Update - Finance

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: finance.manage
- Source: `app/Http/Controllers/GrowyaFinanceController.php:137`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `business`, `entry`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| amount | number | Required | required; numeric; max:999999999999.99 |
| entry_date | string | Required | required; date_format:Y-m-d |
| payment_method | string | Required | required; Allowed: cash, upi, bank, card, cheque, other |
| reference | string | Optional | nullable; string; max:140 |
| description | string | Optional | nullable; string; max:500 |

Example payload (IDs must be replaced):
```json
{
  "amount": 100,
  "entry_date": "2026-10-20",
  "payment_method": "cash"
}
```

Response: `message` and web `location`; no guaranteed resource object.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Mutation returns message and location (web URL); there is no guaranteed newly created resource data/id. Refetch the appropriate API list/show rather than following a web redirect with a bearer token.

Exact validation blocks:

`app/Http/Controllers/GrowyaFinanceController.php:141`
```php
[
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(['cash', 'upi', 'bank', 'card', 'cheque', 'other'])],
            'reference' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:500'],
        ]
```

## GET /dashboard

List - Dashboard

- Group: 19 Dashboards analytics and finance
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/GrowyaDashboardController.php:19`
- Rate limits: 120 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| business_id | integer | Optional | nullable; integer; min:1 |

Domain response data keys: `data.usersCount`, `data.businessesCount`, `data.pendingCount`, `data.approvedCount`, `data.rejectedCount`, `data.ordersCount`, `data.revenueTotal`, `data.latestPending`, `data.latestOrders`, `data.followingCount`, `data.reviewsCount`, `data.businesses`, `data.recentOrders`, `data.business`, `data.stats`, `data.recentEnquiries`, `data.upcomingAppointments`, `data.canProducts`, `data.canServices`, `data.canOrders`, `data.canStaff`, `data.canOverview`, `data.workspaceRole`.

Exact validation blocks:

`app/Http/Controllers/GrowyaDashboardController.php:52`
```php
['business_id' => ['nullable', 'integer', 'min:1']]
```

## POST /activity-events

Create or save - Activity

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ActivityController.php:17`
- Rate limits: 30 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| resource_type | string | Required | required; Allowed: business, product, service, offer |
| resource_id | integer | Required | required; integer; min:1 |
| event_type | string | Required | required; Allowed: view, call_click, whatsapp_click, map_click, share |

Example payload (IDs must be replaced):
```json
{
  "resource_type": "business",
  "resource_id": 1,
  "event_type": "view"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

Exact validation blocks:

`app/Http/Controllers/Extensions/ActivityController.php:18`
```php
['resource_type' => ['required', Rule::in(['business', 'product', 'service', 'offer'])], 'resource_id' => ['required', 'integer', 'min:1'], 'event_type' => ['required', Rule::in(['view', 'call_click', 'whatsapp_click', 'map_click', 'share'])]]
```

## POST /admin/businesses/{business}/ai/credits

Grant - Ai

- Group: 20 New module workflows
- Access: Super admin
- Payload: application/json
- Module permission: ai.credits.manage
- Source: `app/Http/Controllers/Extensions/AiController.php:79`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| credits | integer | Required | required; integer; min:1; max:1000000 |
| request_key | string | Required | required; string; max:100 |

Example payload (IDs must be replaced):
```json
{
  "credits": 100,
  "request_key": "{{request_key}}"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AiController.php:81`
```php
['credits' => ['required', 'integer', 'min:1', 'max:1000000'], 'request_key' => ['required', 'string', 'max:100']]
```

## GET /admin/tools/{kind}

List - Admin Tools

- Group: 20 New module workflows
- Access: Super admin
- Payload: query
- Module permission: kind-specific platform permission
- Source: `app/Http/Controllers/Extensions/AdminToolsController.php:31`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `kind`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /admin/tools/{kind}

Save - Admin Tools

- Group: 20 New module workflows
- Access: Super admin
- Payload: multipart/form-data
- Module permission: kind-specific platform permission
- Source: `app/Http/Controllers/Extensions/AdminToolsController.php:38`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `kind`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: open, reviewing, resolved, dismissed |
| group_name | string | Required | required |
| key_name | string | Required | required; string; max:60 |
| value | string | Optional | nullable; string; max:3000 |
| is_public | boolean | Required | required; boolean |
| name | string | Required | required; string; max:200 |
| festival_date | string | Required | required; date_format:Y-m-d |
| default_caption | string | Optional | nullable; string; max:2200 |
| default_prompt | string | Optional | nullable; string; max:8000 |
| region_filters | array | Optional | nullable; array; max:50 |
| region_filters.* | string | Optional | string; max:100 |
| placement | string | Required | required; Allowed: home, marketplace, showroom |
| city_id | integer | Optional | nullable; integer; exists:cities,id |
| image | file | Required | nullable; required; image; mimes:jpg,jpeg,png,webp; max:5120 |
| cta_text | string | Optional | nullable; string; max:100 |
| cta_url | string | Optional | nullable; url; max:2048 |
| starts_at | date | Optional | nullable; date |
| ends_at | date | Optional | nullable; date; after:starts_at |
| sort_order | integer | Required | required; integer; min:0; max:10000 |

Example payload (IDs must be replaced):
```json
{
  "placement": "home",
  "sort_order": 0,
  "status": "active"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AdminToolsController.php:46`
```php
['status' => ['required', Rule::in(['open', 'reviewing', 'resolved', 'dismissed'])]]
```

`app/Http/Controllers/Extensions/AdminToolsController.php:49`
```php
['group_name' => ['required', Rule::in(array_keys(self::SETTINGS))], 'key_name' => ['required', 'string', 'max:60'], 'value' => ['nullable', 'string', 'max:3000'], 'is_public' => ['required', 'boolean']]
```

`app/Http/Controllers/Extensions/AdminToolsController.php:56`
```php
['name' => ['required', 'string', 'max:200'], 'festival_date' => ['required', 'date_format:Y-m-d'], 'default_caption' => ['nullable', 'string', 'max:2200'], 'default_prompt' => ['nullable', 'string', 'max:8000'], 'region_filters' => ['nullable', 'array', 'max:50'], 'region_filters.*' => ['string', 'max:100'], 'status' => ['required', Rule::in(['active', 'inactive'])]]
```

`app/Http/Controllers/Extensions/AdminToolsController.php:58`
```php
['placement' => ['required', Rule::in(['home', 'marketplace', 'showroom'])], 'city_id' => ['nullable', 'integer', 'exists:cities,id'], 'image' => [$row->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'cta_text' => ['nullable', 'string', 'max:100'], 'cta_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'sort_order' => ['required', 'integer', 'min:0', 'max:10000'], 'status' => ['required', Rule::in(['active', 'inactive'])]]
```

## DELETE /admin/tools/{kind}/{item}

Delete or remove - Admin Tools

- Group: 20 New module workflows
- Access: Super admin
- Payload: none
- Module permission: kind-specific platform permission
- Source: `app/Http/Controllers/Extensions/AdminToolsController.php:84`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `kind`, `item`

Response: JSON object; see the native account/permissions contract or original controller payload.

- kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /admin/tools/{kind}/{item}

Save - Admin Tools

- Group: 20 New module workflows
- Access: Super admin
- Payload: multipart/form-data
- Module permission: kind-specific platform permission
- Source: `app/Http/Controllers/Extensions/AdminToolsController.php:38`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `kind`, `item`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| status | string | Required | required; Allowed: open, reviewing, resolved, dismissed |
| group_name | string | Required | required |
| key_name | string | Required | required; string; max:60 |
| value | string | Optional | nullable; string; max:3000 |
| is_public | boolean | Required | required; boolean |
| name | string | Required | required; string; max:200 |
| festival_date | string | Required | required; date_format:Y-m-d |
| default_caption | string | Optional | nullable; string; max:2200 |
| default_prompt | string | Optional | nullable; string; max:8000 |
| region_filters | array | Optional | nullable; array; max:50 |
| region_filters.* | string | Optional | string; max:100 |
| placement | string | Required | required; Allowed: home, marketplace, showroom |
| city_id | integer | Optional | nullable; integer; exists:cities,id |
| image | file | Required | nullable; required; image; mimes:jpg,jpeg,png,webp; max:5120 |
| cta_text | string | Optional | nullable; string; max:100 |
| cta_url | string | Optional | nullable; url; max:2048 |
| starts_at | date | Optional | nullable; date |
| ends_at | date | Optional | nullable; date; after:starts_at |
| sort_order | integer | Required | required; integer; min:0; max:10000 |

Example payload (IDs must be replaced):
```json
{
  "placement": "home",
  "sort_order": 0,
  "status": "active"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

- Platform administration is required. New module tools accept the named platform permission or super admin; business manager labels do not grant it.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AdminToolsController.php:46`
```php
['status' => ['required', Rule::in(['open', 'reviewing', 'resolved', 'dismissed'])]]
```

`app/Http/Controllers/Extensions/AdminToolsController.php:49`
```php
['group_name' => ['required', Rule::in(array_keys(self::SETTINGS))], 'key_name' => ['required', 'string', 'max:60'], 'value' => ['nullable', 'string', 'max:3000'], 'is_public' => ['required', 'boolean']]
```

`app/Http/Controllers/Extensions/AdminToolsController.php:56`
```php
['name' => ['required', 'string', 'max:200'], 'festival_date' => ['required', 'date_format:Y-m-d'], 'default_caption' => ['nullable', 'string', 'max:2200'], 'default_prompt' => ['nullable', 'string', 'max:8000'], 'region_filters' => ['nullable', 'array', 'max:50'], 'region_filters.*' => ['string', 'max:100'], 'status' => ['required', Rule::in(['active', 'inactive'])]]
```

`app/Http/Controllers/Extensions/AdminToolsController.php:58`
```php
['placement' => ['required', Rule::in(['home', 'marketplace', 'showroom'])], 'city_id' => ['nullable', 'integer', 'exists:cities,id'], 'image' => [$row->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'cta_text' => ['nullable', 'string', 'max:100'], 'cta_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'sort_order' => ['required', 'integer', 'min:0', 'max:10000'], 'status' => ['required', Rule::in(['active', 'inactive'])]]
```

## POST /auth/otp/send

Send - Otp

- Group: 20 New module workflows
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/OtpController.php:17`
- Rate limits: 3 requests / 10 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |

Example payload (IDs must be replaced):
```json
{
  "phone": "9876543210"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- send returns {message,expires_in}; verify returns {data:{token,token_type,expires_in,user}}. Invalid/expired/used code or ineligible account returns 422. Registration remains the existing /auth/register flow.

Exact validation blocks:

`app/Http/Controllers/Extensions/OtpController.php:18`
```php
['phone' => ['required', 'regex:/^[6-9][0-9]{9}$/']]
```

## POST /auth/otp/verify

Verify - Otp

- Group: 20 New module workflows
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/OtpController.php:41`
- Rate limits: 10 requests / 10 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| phone | string | Required | required; regex:/^[6-9][0-9]{9}$/ |
| code | string | Required | required; digits:6 |
| device_name | string | Required | required; string; max:100 |
| two_factor_code | string | Optional | nullable; string; max:20 |

Example payload (IDs must be replaced):
```json
{
  "phone": "9876543210",
  "code": "123456",
  "device_name": "Flutter Android"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- send returns {message,expires_in}; verify returns {data:{token,token_type,expires_in,user}}. Invalid/expired/used code or ineligible account returns 422. Registration remains the existing /auth/register flow.

Exact validation blocks:

`app/Http/Controllers/Extensions/OtpController.php:42`
```php
['phone' => ['required', 'regex:/^[6-9][0-9]{9}$/'], 'code' => ['required', 'digits:6'], 'device_name' => ['required', 'string', 'max:100'], 'two_factor_code' => ['nullable', 'string', 'max:20']]
```

## GET /businesses/{business}/accounting/accounts

Accounts - Accounting

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: accounting.view
- Source: `app/Http/Controllers/Extensions/AccountingController.php:14`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/accounting/accounts

Account - Accounting

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: accounting.manage
- Source: `app/Http/Controllers/Extensions/AccountingController.php:21`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| code | string | Required | required; max:30; Unique within the server rule scope |
| name | string | Required | required; string; max:150 |
| type | string | Required | required; Allowed: asset, liability, equity, income, expense |
| is_cash | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "code": "CASH",
  "name": "Cash in hand",
  "type": "asset",
  "is_cash": true
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AccountingController.php:23`
```php
['code' => ['required', 'alpha_dash', 'max:30', Rule::unique('growya_journal_accounts', 'code')->where('business_id', $business->id)], 'name' => ['required', 'string', 'max:150'], 'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])], 'is_cash' => ['required', 'boolean']]
```

## GET /businesses/{business}/accounting/journals

List - Accounting

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: accounting.view
- Source: `app/Http/Controllers/Extensions/AccountingController.php:31`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/accounting/journals

Create or save - Accounting

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: accounting.manage
- Source: `app/Http/Controllers/Extensions/AccountingController.php:44`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| lines | array | Required | required; array; max:100 |
| request_key | string | Required | required; string; max:100 |
| entry_date | string | Required | required; date_format:Y-m-d; before_or_equal:today |
| memo | string | Required | required; string; max:500 |
| lines.*.account_id | integer | Required | required; integer |
| lines.*.debit_paise | integer | Required | required; integer; min:0; max:1000000000000 |
| lines.*.credit_paise | integer | Required | required; integer; min:0; max:1000000000000 |

Example payload (IDs must be replaced):
```json
{
  "request_key": "{{request_key}}",
  "entry_date": "2026-10-20",
  "memo": "Opening capital",
  "lines": [
    {
      "account_id": 1,
      "debit_paise": 100000,
      "credit_paise": 0
    },
    {
      "account_id": 2,
      "debit_paise": 0,
      "credit_paise": 100000
    }
  ]
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AccountingController.php:47`
```php
['lines' => ['required', 'array', 'max:100'], 'lines.*.debit_rupees' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000000000'], 'lines.*.credit_rupees' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000000000']]
```

`app/Http/Controllers/Extensions/AccountingController.php:51`
```php
['request_key' => ['required', 'string', 'max:100'], 'entry_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'memo' => ['required', 'string', 'max:500'], 'lines' => ['required', 'array', 'min:2', 'max:100'], 'lines.*.account_id' => ['required', 'integer'], 'lines.*.debit_paise' => ['required', 'integer', 'min:0', 'max:1000000000000'], 'lines.*.credit_paise' => ['required', 'integer', 'min:0', 'max:1000000000000']]
```

## POST /businesses/{business}/accounting/journals/{journal}/reverse

Reverse - Accounting

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: accounting.manage
- Source: `app/Http/Controllers/Extensions/AccountingController.php:57`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`, `journal`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| request_key | string | Required | required; string; max:100 |
| entry_date | string | Required | required; date_format:Y-m-d; before_or_equal:today |
| memo | string | Required | required; string; max:500 |

Example payload (IDs must be replaced):
```json
{
  "request_key": "{{request_key}}",
  "entry_date": "2026-10-20",
  "memo": "Reverse incorrect entry"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AccountingController.php:59`
```php
['request_key' => ['required', 'string', 'max:100'], 'entry_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'memo' => ['required', 'string', 'max:500']]
```

## GET /businesses/{business}/accounting/reports

Reports - Accounting

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: accounting.view
- Source: `app/Http/Controllers/Extensions/AccountingController.php:69`
- Rate limits: 30 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d; after_or_equal:from |

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts {data:account[]}, account/store/reverse {data:record} (201), index {data:paginator} including lines. reports returns {data:{from,to,accounts:[{account_id,code,name,type,opening_paise,debit_paise,credit_paise,closing_paise}],trial_debit_paise,trial_credit_paise,income_paise,expense_paise,profit_paise,cash_in_paise,cash_out_paise,net_cash_movement_paise}}. Journal/account amounts are integer paise. Journal lines must balance and belong to the selected business; no update/delete endpoint exists. Reversal creates another balanced journal. Cash flow follows accounts explicitly marked is_cash; do not add basic-finance ledger totals to journal totals.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AccountingController.php:71`
```php
['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]
```

## GET /businesses/{business}/ai

List - Ai

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: ai.view
- Source: `app/Http/Controllers/Extensions/AiController.php:19`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/ai

Create or save - Ai

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: ai.use
- Source: `app/Http/Controllers/Extensions/AiController.php:26`
- Rate limits: 5 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| generation_type | file | Required | required; image; Allowed: text, image |
| prompt | string | Required | required; string; min:5; max:8000 |
| request_key | string | Required | required; string; max:100 |

Example payload (IDs must be replaced):
```json
{
  "generation_type": "text",
  "prompt": "Write an opening caption for my jewellery showroom.",
  "request_key": "{{request_key}}"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/AiController.php:29`
```php
['generation_type' => ['required', Rule::in(['text', 'image'])], 'prompt' => ['required', 'string', 'min:5', 'max:8000'], 'request_key' => ['required', 'string', 'max:100']]
```

## GET /businesses/{business}/ai/credits

Credits - Ai

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: ai.view
- Source: `app/Http/Controllers/Extensions/AiController.php:72`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/ai/generations/{generation}

Read detail - Ai

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: ai.view
- Source: `app/Http/Controllers/Extensions/AiController.php:53`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`, `generation`

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/ai/media/{media}

Media - Ai

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: ai.view
- Source: `app/Http/Controllers/Extensions/AiController.php:63`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`, `media`

Response: binary/stream download.

- index returns {data:{wallet:{total_credits,used_credits,available_credits},generations:paginator}}; store {data:generation} (202); show {data:generation_with_media}; credits {data:{wallet,transactions:paginator}}; grant {data:wallet}; media returns protected bytes. GET ai/generations/{id} polls queued/processing/completed/failed. Output text is data.output_json.text. Media download_path is relative to /api/v1.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## GET /businesses/{business}/analytics/daily

Daily - Activity

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: analytics.view
- Source: `app/Http/Controllers/Extensions/ActivityController.php:69`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| from | string | Optional | nullable; date_format:Y-m-d |
| to | string | Optional | nullable; date_format:Y-m-d; after_or_equal:from |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/ActivityController.php:71`
```php
['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]
```

## GET /businesses/{business}/commerce-policy

Read detail - Commerce

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: commerce.view
- Source: `app/Http/Controllers/Extensions/CommerceController.php:13`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Response: JSON object; see the native account/permissions contract or original controller payload.

- show/save return {data:policy|null}. API amounts are integer paise; tax_basis_points=300 means 3.00%. Web forms accept rupees/percent and convert server side. Defaults disable shipping/GST/OTP/POD. GST uses one configured exclusive business rate, after discount and including shipping; no rate is inferred.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## PUT /businesses/{business}/commerce-policy

Save - Commerce

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: commerce.manage
- Source: `app/Http/Controllers/Extensions/CommerceController.php:20`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| shipping_enabled | boolean | Required | required; boolean |
| shipping_paise | integer | Required | required; integer; min:0; max:10000000 |
| free_above_paise | integer | Optional | nullable; integer; min:0; max:1000000000 |
| tax_enabled | boolean | Required | required; boolean |
| tax_basis_points | integer | Required | required; integer; min:0; max:4000 |
| seller_state | string | Conditional | required_if:tax_enabled,1; nullable; string; exists:states,name |
| delivery_otp_required | boolean | Required | required; boolean |
| delivery_proof_required | boolean | Required | required; boolean |

Example payload (IDs must be replaced):
```json
{
  "shipping_enabled": false,
  "shipping_paise": 0,
  "free_above_paise": null,
  "tax_enabled": false,
  "tax_basis_points": 0,
  "seller_state": null,
  "delivery_otp_required": false,
  "delivery_proof_required": false
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- show/save return {data:policy|null}. API amounts are integer paise; tax_basis_points=300 means 3.00%. Web forms accept rupees/percent and convert server side. Defaults disable shipping/GST/OTP/POD. GST uses one configured exclusive business rate, after discount and including shipping; no rate is inferred.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/CommerceController.php:23`
```php
['shipping_rupees' => ['required', 'decimal:0,2', 'min:0', 'max:100000'], 'free_above_rupees' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000000'], 'tax_percent' => ['required', 'decimal:0,2', 'min:0', 'max:40']]
```

`app/Http/Controllers/Extensions/CommerceController.php:26`
```php
['shipping_enabled' => ['required', 'boolean'], 'shipping_paise' => ['required', 'integer', 'min:0', 'max:10000000'], 'free_above_paise' => ['nullable', 'integer', 'min:0', 'max:1000000000'], 'tax_enabled' => ['required', 'boolean'], 'tax_basis_points' => ['required', 'integer', 'min:0', 'max:4000'], 'seller_state' => ['required_if:tax_enabled,1', 'nullable', 'string', 'exists:states,name'], 'delivery_otp_required' => ['required', 'boolean'], 'delivery_proof_required' => ['required', 'boolean']]
```

## GET /businesses/{business}/gateway-payments

List - Gateway

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: payments.view
- Source: `app/Http/Controllers/Extensions/GatewayController.php:51`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/gateway-payments/reconcile

Reconcile - Gateway

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: payments.manage
- Source: `app/Http/Controllers/Extensions/GatewayController.php:59`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| payment_id | string | Required | required; string; max:100 |
| gateway_payment_id | integer | Required | required; integer |

Example payload (IDs must be replaced):
```json
{
  "gateway_payment_id": 1,
  "payment_id": "{{provider_payment_id}}"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/GatewayController.php:61`
```php
['payment_id' => ['required', 'string', 'max:100'], 'gateway_payment_id' => ['required', 'integer']]
```

## POST /businesses/{business}/orders/{order}/delivery/complete

Complete - Delivery Proof

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: delivery.manage
- Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:96`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `business`, `order`

Response: JSON object; see the native account/permissions contract or original controller payload.

- send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/orders/{order}/delivery/proof

Upload - Delivery Proof

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: delivery.manage
- Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:71`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`, `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| proof | file | Required | required; image; mimes:jpg,jpeg,png,webp; max:5120 |
| recipient_name | string | Required | required; string; max:120 |

Example payload (IDs must be replaced):
```json
{
  "recipient_name": "Demo Recipient"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/DeliveryProofController.php:73`
```php
['proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'recipient_name' => ['required', 'string', 'max:120']]
```

## POST /businesses/{business}/orders/{order}/delivery/send-otp

Send - Delivery Proof

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: delivery.manage
- Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:27`
- Rate limits: 5 requests / 1 minute(s)
- Path inputs: `business`, `order`

Response: JSON object; see the native account/permissions contract or original controller payload.

- send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/orders/{order}/delivery/verify-otp

Verify - Delivery Proof

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: delivery.manage
- Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:49`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`, `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| code | string | Required | required; digits:6 |

Example payload (IDs must be replaced):
```json
{
  "code": "123456"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/DeliveryProofController.php:51`
```php
['code' => ['required', 'digits:6']]
```

## GET /businesses/{business}/social/accounts

Accounts - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: social.view
- Source: `app/Http/Controllers/Extensions/SocialController.php:24`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## DELETE /businesses/{business}/social/accounts/{account}

Disconnect - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:109`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `business`, `account`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/social/candidates

Candidates - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:82`
- Rate limits: 30 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| connection_key | string | Required | required; string; size:64 |

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/SocialController.php:84`
```php
['connection_key' => ['required', 'string', 'size:64']]
```

## POST /businesses/{business}/social/connect

Connect - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:31`
- Rate limits: 5 requests / 1 minute(s)
- Path inputs: `business`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/social/posts

Posts - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: social.view
- Source: `app/Http/Controllers/Extensions/SocialController.php:118`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/social/posts

Create or save - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:125`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| caption | string | Required | required; string; max:2200 |
| account_ids | array | Required | required; array; min:1; max:10 |
| account_ids.* | integer | Required | required; integer; distinct; Existing allowed record; referential scope is checked server side |
| image | file | Optional | nullable; image; mimes:jpg,jpeg,png; max:8192 |
| publish_mode | string | Required | required; Allowed: draft, now, scheduled |
| scheduled_at | date | Conditional | required_if:publish_mode,scheduled; nullable; date; after:now |
| request_key | string | Required | required; string; max:100 |
| media_type | file | Optional | image |

Example payload (IDs must be replaced):
```json
{
  "caption": "Welcome to our showroom.",
  "account_ids": [
    1
  ],
  "publish_mode": "draft",
  "request_key": "{{request_key}}"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/SocialController.php:127`
```php
['caption' => ['required', 'string', 'max:2200'], 'account_ids' => ['required', 'array', 'min:1', 'max:10'], 'account_ids.*' => ['required', 'integer', 'distinct', Rule::exists('social_accounts', 'id')->where('business_id', $business->id)->where('connection_status', 'connected')], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192'], 'publish_mode' => ['required', Rule::in(['draft', 'now', 'scheduled'])], 'scheduled_at' => ['required_if:publish_mode,scheduled', 'nullable', 'date', 'after:now'], 'request_key' => ['required', 'string', 'max:100']]
```

`app/Http/Controllers/Extensions/SocialController.php:156`
```php
['media_type' => 'image', 'file_url' => $path, 'sort_order' => 0]
```

## POST /businesses/{business}/social/posts/{post}/cancel

Cancel - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:188`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`, `post`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/social/posts/{post}/publish

Publish - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:174`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`, `post`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## GET /businesses/{business}/social/schedules

Schedules - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: social.view
- Source: `app/Http/Controllers/Extensions/SocialController.php:202`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `business`

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/social/schedules

Schedule - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:209`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| social_account_id | integer | Required | required; integer; Existing allowed record; referential scope is checked server side |
| frequency | string | Required | required; Allowed: daily, weekly |
| run_time | string | Required | required; date_format:H:i |
| days_of_week | array | Conditional | required_if:frequency,weekly; nullable; array; min:1; max:7 |
| days_of_week.* | integer | Optional | integer; between:0,6; distinct |
| timezone | string | Required | required |
| caption | string | Required | required; string; max:2200 |
| image | file | Optional | nullable; image; mimes:jpg,jpeg,png; max:8192 |

Example payload (IDs must be replaced):
```json
{
  "social_account_id": 1,
  "frequency": "weekly",
  "run_time": "09:00",
  "days_of_week": [
    1,
    3,
    5
  ],
  "timezone": "Asia/Kolkata",
  "caption": "Discover our latest collection."
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/SocialController.php:211`
```php
['social_account_id' => ['required', 'integer', Rule::exists('social_accounts', 'id')->where('business_id', $business->id)->where('connection_status', 'connected')], 'frequency' => ['required', Rule::in(['daily', 'weekly'])], 'run_time' => ['required', 'date_format:H:i'], 'days_of_week' => ['required_if:frequency,weekly', 'nullable', 'array', 'min:1', 'max:7'], 'days_of_week.*' => ['integer', 'between:0,6', 'distinct'], 'timezone' => ['required', 'timezone'], 'caption' => ['required', 'string', 'max:2200'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192']]
```

## DELETE /businesses/{business}/social/schedules/{schedule}

Stop schedule - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:239`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `business`, `schedule`

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

## POST /businesses/{business}/social/select

Select - Social

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: social.manage
- Source: `app/Http/Controllers/Extensions/SocialController.php:91`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| connection_key | string | Required | required; string; size:64 |
| page_id | string | Required | required; string; max:100 |
| platform | string | Required | required; Allowed: facebook, instagram |

Example payload (IDs must be replaced):
```json
{
  "connection_key": "{{connection_key}}",
  "page_id": "{{page_id}}",
  "platform": "facebook"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- accounts returns {data:account[]}; connect {data:{authorization_url,connection_key,expires_in}}; candidates {data:{error,pages:[{id,name,instagram}]}}; select {data:account} (201); posts/schedules {data:paginator}; store/schedule {data:resource} (201); publish {data:{queued:true}} (202); cancel/stop/disconnect return data flags. Tokens are hidden/encrypted. Callback is WEB GET /integrations/meta/callback, authenticated by single-use state. Flutter keeps connection_key and polls candidates after browser authorization, then selects page_id and platform.

- Business permission shown here is the module middleware gate. Controller checks, active membership, tenant ownership, approval, quotas and participant restrictions still apply.

Exact validation blocks:

`app/Http/Controllers/Extensions/SocialController.php:93`
```php
['connection_key' => ['required', 'string', 'size:64'], 'page_id' => ['required', 'string', 'max:100'], 'platform' => ['required', Rule::in(['facebook', 'instagram'])]]
```

## GET /content

Public content - Admin Tools

- Group: 20 New module workflows
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/AdminToolsController.php:109`
- Rate limits: 60 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| placement | string | Optional | nullable; Allowed: home, marketplace, showroom |
| city_id | integer | Optional | nullable; integer; exists:cities,id |

Response: JSON object; see the native account/permissions contract or original controller payload.

- kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

Exact validation blocks:

`app/Http/Controllers/Extensions/AdminToolsController.php:110`
```php
['placement' => ['nullable', Rule::in(['home', 'marketplace', 'showroom'])], 'city_id' => ['nullable', 'integer', 'exists:cities,id']]
```

## GET /devices

List - Device

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/DeviceController.php:12`
- Rate limits: 60 requests / 1 minute(s)

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.

## POST /devices

Create or save - Device

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/DeviceController.php:17`
- Rate limits: 20 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| fcm_token | string | Required | required; string; min:20; max:4096 |
| platform | string | Required | required; Allowed: android, ios, web |
| app_version | string | Optional | nullable; string; max:40 |

Example payload (IDs must be replaced):
```json
{
  "fcm_token": "{{fcm_token}}",
  "platform": "android",
  "app_version": "1.0.0"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.

Exact validation blocks:

`app/Http/Controllers/Extensions/DeviceController.php:18`
```php
['fcm_token' => ['required', 'string', 'min:20', 'max:4096'], 'platform' => ['required', Rule::in(['android', 'ios', 'web'])], 'app_version' => ['nullable', 'string', 'max:40']]
```

## DELETE /devices/{device}

Delete or remove - Device

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/DeviceController.php:34`
- Rate limits: 30 requests / 1 minute(s)
- Path inputs: `device`

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.

## GET /discovery/search

Search - Activity

- Group: 20 New module workflows
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ActivityController.php:78`
- Rate limits: 30 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| query | string | Required | required; string; max:120 |
| city_id | integer | Optional | nullable; integer; exists:cities,id |

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

Exact validation blocks:

`app/Http/Controllers/Extensions/ActivityController.php:79`
```php
['query' => ['required', 'string', 'max:120'], 'city_id' => ['nullable', 'integer', 'exists:cities,id']]
```

## POST /my-orders/{order}/gateway

Read create form options - Gateway

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/GatewayController.php:13`
- Rate limits: 5 requests / 1 minute(s)
- Path inputs: `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| request_key | string | Required | required; string; max:100 |

Example payload (IDs must be replaced):
```json
{
  "request_key": "{{request_key}}"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

Exact validation blocks:

`app/Http/Controllers/Extensions/GatewayController.php:15`
```php
['request_key' => ['required', 'string', 'max:100']]
```

## POST /my-orders/{order}/gateway/verify

Verify - Gateway

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/GatewayController.php:22`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `order`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| razorpay_order_id | string | Required | required; string; max:100 |
| razorpay_payment_id | string | Required | required; string; max:100 |
| razorpay_signature | string | Required | required; string; size:64 |

Example payload (IDs must be replaced):
```json
{
  "razorpay_order_id": "{{provider_order_id}}",
  "razorpay_payment_id": "{{provider_payment_id}}",
  "razorpay_signature": "{{razorpay_signature}}"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.

Exact validation blocks:

`app/Http/Controllers/Extensions/GatewayController.php:25`
```php
['razorpay_order_id' => ['required', 'string', 'max:100'], 'razorpay_payment_id' => ['required', 'string', 'max:100'], 'razorpay_signature' => ['required', 'string', 'size:64']]
```

## GET /orders/{order}/delivery-proof

Download attachment - Delivery Proof

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/DeliveryProofController.php:115`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `order`

Response: binary/stream download.

- send {data:{sent:true,expires_in:300}}; verify {data:{verified:true}}; upload {data:{uploaded:true}} (201); complete {data:{delivered:true}}; file protected streamed bytes. Only own-business shipped orders accept confirmation. OTP has 5 attempts, expires in 5 minutes and is usable once; verification is valid for completion for 30 minutes. Legacy order-status and delivery-stage actions enforce the same policy.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## POST /push-deliveries/{delivery}/ack

Acknowledge - Device

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/DeviceController.php:41`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `delivery`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| event | string | Required | required; Allowed: delivered, opened |

Example payload (IDs must be replaced):
```json
{
  "event": "opened"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- index returns {data:[{id,platform,app_version,is_active,last_seen_at}]}; store returns {data:device} (201); destroy/acknowledge return {message}. No FCM token is exposed. FCM data supplies delivery_id for acknowledgements.

Exact validation blocks:

`app/Http/Controllers/Extensions/DeviceController.php:42`
```php
['event' => ['required', Rule::in(['delivered', 'opened'])]]
```

## DELETE /recently-viewed

Clear - Activity

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ActivityController.php:41`
- Rate limits: 10 requests / 1 minute(s)

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

## GET /recently-viewed

Read history - Activity

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ActivityController.php:25`
- Rate limits: 60 requests / 1 minute(s)

Additional read/query inputs: `page`. These are read by the controller; not all have explicit validator limits.

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

## POST /reports

Report - Admin Tools

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/AdminToolsController.php:100`
- Rate limits: 5 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| resource_type | string | Required | required; Allowed: business, product, service, offer |
| resource_id | integer | Required | required; integer; min:1 |
| reason | string | Required | required; string; min:10; max:3000 |

Example payload (IDs must be replaced):
```json
{
  "resource_type": "business",
  "resource_id": 1,
  "reason": "This listing appears to contain inaccurate information."
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- kind is banners|festivals|settings|reports|audit; index returns {data:paginator}; save {data:record}; destroy {data:{deleted:true}}. Audit is read-only, reports update-only. Permissions by kind: banners.manage, festivals.manage, settings.manage, reports.manage, audit.view. Settings allow only branding(site_title,support_email,support_phone), marketplace(welcome_text,show_banner), notifications(help_text), never secrets. Public content returns {data:{banners:[{id,media_url,cta_text,cta_url}],settings:[],festivals:[]}}. report creates an open moderation report for an existing active public resource.

Exact validation blocks:

`app/Http/Controllers/Extensions/AdminToolsController.php:101`
```php
['resource_type' => ['required', Rule::in(['business', 'product', 'service', 'offer'])], 'resource_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:10', 'max:3000']]
```

## GET /review-photos/{photo}

Download attachment - Review Photo

- Group: 20 New module workflows
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ReviewPhotoController.php:41`
- Rate limits: 120 requests / 1 minute(s)
- Path inputs: `photo`

Response: binary/stream download.

- store returns {data:{images:[{id,url}]}} (201), destroy {data:{removed:true}}; file returns streamed bytes. Uploads require review ownership, max 5 photos total, 3072 KB each. Upload resets approval to pending. Public image reads are approved-only; an owner may use bearer auth to read pending images.

- Handle bytes according to Content-Type/Content-Disposition. For a protected web attachment URL returned elsewhere, use the matching /api/v1 endpoint with Authorization, not the session-authenticated web URL.

## POST /reviews/{review}/photos

Create or save - Review Photo

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: multipart/form-data
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ReviewPhotoController.php:14`
- Rate limits: 10 requests / 1 minute(s)
- Path inputs: `review`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| images | array | Required | required; array; min:1; max:5 |
| images.* | file | Required | required; image; mimes:jpg,jpeg,png,webp; max:3072 |

Response: JSON object; see the native account/permissions contract or original controller payload.

- store returns {data:{images:[{id,url}]}} (201), destroy {data:{removed:true}}; file returns streamed bytes. Uploads require review ownership, max 5 photos total, 3072 KB each. Upload resets approval to pending. Public image reads are approved-only; an owner may use bearer auth to read pending images.

Exact validation blocks:

`app/Http/Controllers/Extensions/ReviewPhotoController.php:16`
```php
['images' => ['required', 'array', 'min:1', 'max:5'], 'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']]
```

## DELETE /reviews/{review}/photos/{photo}

Delete or remove - Review Photo

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: none
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ReviewPhotoController.php:55`
- Rate limits: 20 requests / 1 minute(s)
- Path inputs: `review`, `photo`

Response: JSON object; see the native account/permissions contract or original controller payload.

- store returns {data:{images:[{id,url}]}} (201), destroy {data:{removed:true}}; file returns streamed bytes. Uploads require review ownership, max 5 photos total, 3072 KB each. Upload resets approval to pending. Public image reads are approved-only; an owner may use bearer auth to read pending images.

## POST /share-links

Share - Activity

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ActivityController.php:48`
- Rate limits: 20 requests / 1 minute(s)

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| resource_type | string | Required | required; Allowed: business, product, service, offer |
| resource_id | integer | Required | required; integer; min:1 |

Example payload (IDs must be replaced):
```json
{
  "resource_type": "business",
  "resource_id": 1
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

Exact validation blocks:

`app/Http/Controllers/Extensions/ActivityController.php:49`
```php
['resource_type' => ['required', Rule::in(['business', 'product', 'service', 'offer'])], 'resource_id' => ['required', 'integer', 'min:1']]
```

## GET /share-links/{code}

Resolve - Activity

- Group: 20 New module workflows
- Access: Public
- Payload: query
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/ActivityController.php:57`
- Rate limits: 60 requests / 1 minute(s)
- Path inputs: `code`

Response: JSON object; see the native account/permissions contract or original controller payload.

- history, search and daily return {data:paginator}; share returns {data:{url,code,expires_at}}; resolve returns {data:{url,resource_type,resource_id}}; store/clear return {data:{recorded|cleared:true}}. History/search omit private or inactive public resources.

## POST /shops/{business}/quote

Quote - Quote

- Group: 20 New module workflows
- Access: Authenticated with business or participant checks
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/QuoteController.php:20`
- Rate limits: 30 requests / 1 minute(s)
- Path inputs: `business`

| Field | Type | Requirement | Constraints |
|---|---|---|---|
| address_id | integer | Optional | nullable; integer; Existing allowed record; referential scope is checked server side |
| state | string | Conditional | required_without:address_id; nullable; string; max:100 |
| coupon_code | string | Optional | nullable; string; max:40 |

Example payload (IDs must be replaced):
```json
{
  "state": "Uttar Pradesh"
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- returns {data:{subtotal_paise,discount_paise,shipping_paise,tax_paise,total_paise,snapshot}}. Read before checkout. Checkout accepts optional quoted_total_paise and rejects changed totals before stock/order writes. Existing checkout place action returns HTTP 200 via compatibility adapter. Saved address state takes precedence over a supplied manual state.

Exact validation blocks:

`app/Http/Controllers/Extensions/QuoteController.php:22`
```php
['address_id' => ['nullable', 'integer', Rule::exists('user_addresses', 'id')->where('user_id', $r->user()->id)], 'state' => ['required_without:address_id', 'nullable', 'string', 'max:100'], 'coupon_code' => ['nullable', 'string', 'max:40']]
```

## POST /webhooks/razorpay

Webhook - Gateway

- Group: 20 New module workflows
- Access: Public
- Payload: application/json
- Module permission: Controller scope checks apply
- Source: `app/Http/Controllers/Extensions/GatewayController.php:35`
- Rate limits: 120 requests / 1 minute(s)

Example payload (IDs must be replaced):
```json
{
  "event": "payment.captured",
  "payload": {
    "payment": {
      "entity": {
        "id": "pay_EXAMPLE",
        "order_id": "order_EXAMPLE"
      }
    }
  }
}
```

Response: JSON object; see the native account/permissions contract or original controller payload.

- create returns {data:{id,key_id,provider_order_id,amount_paise,currency,status}} (201); verify/reconcile {data:{paid,requires_review?,order_id}}; index {data:paginator}; webhook {received:true}. Send Flutter Razorpay success fields verbatim to verify; trust server paid only. Signature alone does not settle an order: server fetches payment and checks captured status, amount, currency and provider order. Repeated callbacks are idempotent. Unknown create outcomes are reconciled by provider receipt growya-ID, never automatically charged again. Late capture on cancelled/ineligible orders becomes captured_review. Refund execution remains a manual Razorpay-operator step; existing return/refund records do not issue provider refunds.
