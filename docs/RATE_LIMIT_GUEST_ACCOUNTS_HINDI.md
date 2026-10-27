# Groya: 429, guest browsing aur account type — 26 October 2026

## Kya fix hua

Laravel numeric `throttle:N,1` middleware ke counters default mein same authenticated user ya anonymous domain/IP signature share kar rahe the. Chat/notification reads, message send, enquiry, search aur API action ke layered limits ek doosre ka budget consume kar rahe the.

`app/Http/Middleware/ThrottleByRoute.php` ab numeric budgets ko route name/template + limit/window se namespace karta hai. Record ID badalne par naya budget nahi milta. Same route par layered outer/inner budgets bhi alag hain. Named login, two-factor aur AI generation limiters apna existing deliberate shared scope rakhte hain. Permissions, FCM credentials aur credit accounting mein koi badlav nahi.

Web/API budgets (per user; anonymous requests use original framework domain/IP signature):

| Action | Per minute |
|---|---:|
| Chat poll | 120 |
| Notification poll | 90 |
| Unread chat count | 90 |
| Message send | 60 |
| Product/service enquiry submit | 5 |

Feature API routes ka existing outer 120/min budget bhi route-scoped hai. Har user ki total application traffic ek common 120 bucket mein ab count nahi hoti.

Chat/notification browser polling overlap aur 3-second event bursts ko coalesce karti hai; Retry-After seconds/HTTP-date respect karti hai; connection errors par delay badhta hai. Hidden page par polling pause; expired session par chat/bell stop. Push config requests mein simultaneous GET deduplication aur per-URL cooldown hai. Updated script query versions browser cache refresh karte hain.

Genuine rate limit exceed hone par 429 ab bhi expected hai. External AI provider ya hosting/proxy ki apni 429 is change se remove nahi hoti.

## Guest flow

Login/register screen par **Continue as guest** → marketplace. Guest koi fake account nahi banata.

Guest approved + active businesses, unke active products, variations/details, services, galleries/offers aur public search dekh sakta hai. Pending/inactive/private content public nahi kiya gaya.

Product/service enquiry ke GET form aur POST submit dono login protected hain. Existing cart/checkout/chat protection maintained hai; cart add bhi login maangta hai. Web par enquiry link → login → registration ya login complete → wahi enquiry form. Business registration bhi pending enquiry ko complete karne deta hai; without pending destination business onboarding default rehta hai. Login ke baad POST automatic replay nahi hota; customer form confirm karke submit karta hai.

## Users list

`/users` par **Account type / Roles** column aur Personal/Business filter add kiya hai; access modal mein bhi type dikhta hai. Search 400ms debounce use karti hai.

Existing registration field `account_type=personal|business` already `users.user_type=customer|owner` save karta hai. Usi stored value ko display kiya hai. Roles alag show hote hain; business selection se super-admin/staff permissions nahi milti. Other stored user types ko unke own label se dikhaya jata hai. Purane customer/owner records ko migration ke bina labels milte hain. Historic registration intent independently store nahi hua tha; display existing `user_type` par based hai.

## Deploy

Uploaded project root mein changed/new files copy karein, ya full project deploy karein. Server ki .env, Firebase JSON, user uploads aur database ko apni current copies par rakhein.

```bash
composer dump-autoload
php artisan optimize:clear
php artisan queue:restart
```

Hard refresh: Ctrl+Shift+R. Database migration/seeder/npm build is fix ke liye required nahi. **migrate:fresh mat chalana.** Running queue worker ko hosting process manager se resume rakhein.

If previous active code cache/opcache deployment ke baad old code serve kare, hosting panel se PHP restart karein. Agar 429 persists, browser Network se failing request ka method/URL, Retry-After, response JSON/body aur relevant server log share karein; token/credentials share na karein.

## Flutter/API developer ke liye

Base: `https://YOUR-DOMAIN/api/v1`.

Guest browsing mein protected seller-management `GET /businesses` use nahi karein. Public endpoints use karein:

```http
GET /marketplace
GET /search?type=products
GET /search?type=services
GET /b/{businessSlug}
GET /b/{businessSlug}/products
GET /b/{businessSlug}/products/{productSlug}
GET /b/{businessSlug}/services
GET /b/{businessSlug}/services/{serviceSlug}
```

Headers: `Accept: application/json`. No bearer token required. Existing response envelope remains `data` (for example list: `data.businesses.data`, product detail: `data.product`). Public showroom business/child paths use slugs; enquiry paths below use numeric IDs.

Protected enquiry endpoints now require real login, `Authorization: Bearer <TOKEN>` with growya token, otherwise HTTP 401:

```http
GET  /products/{businessId}/{productId}/enquiry
POST /products/{businessId}/{productId}/enquiry
GET  /s/{businessId}/{serviceId}/enquiry
POST /s/{businessId}/{serviceId}/enquiry
GET  /shops/{businessId}/checkout
POST /shops/{businessId}/checkout
```

Product enquiry POST body:

```json
{"name":"Customer name","phone":"9876543210","quantity":1,"message":"Please share availability"}
```

Service enquiry POST body:

```json
{"customer_name":"Customer name","phone":"9876543210","message":"Please share details"}
```

Flutter should retain pending screen/business/product/service context when showing login/register; after successful auth return to that screen and let the customer confirm. This ZIP is Laravel code; Flutter navigation/session/UI must be updated in the Flutter project separately.

Login/register/me explicit user objects now add `account_type: personal|business|null`, existing `user_type` remains for compatibility. Null means another stored user type (e.g. staff); account purpose is not a permission grant.

Super-admin users API:

```http
GET /users?account_type=personal
GET /users?account_type=business&search=shopper
```

Paginated `data.data[]` includes `user_type` and computed `account_type`. Query is still super-admin only. Missing filter returns all users; invalid filter returns 422.

Rate-limit JSON example (body is informational; headers determine retry time):

```json
{"message":"Too many requests. Please try again in 60 seconds.","error_code":"app_rate_limit","retry_after":60}
```

On 429, respect `Retry-After`; do not retry immediately. Coalesce poll/push refresh, pause when screen inactive, avoid duplicate timers. Suggested chat polling 8 seconds, notification polling 20 seconds, no concurrent same-screen polls. Message send/enquiry/checkout should never auto-replay on 429. Provider-specific AI errors retain their existing response contract.

## Validation

- Full suite: 272 tests passed, 1,113 assertions.
- After final send-limit adjustment/formatting: focused 77 tests passed, 496 assertions (new regressions, auth, browser push, AI studio).
- 18 new regression cases: real bearer-token API polls, web polling+send, scoped numeric budgets, layered budgets, shared named limits, Retry-After/recovery, guest catalog pages, inactive/unapproved visibility, protected enquiries/checkout, registration return, attribution, admin list/filter/access.
- Node polling behavior: 10 assertions passed (chat and bell cooldown, event burst coalescing, recovery, navigation teardown).
- All Blade templates compiled; chat.js and push.js syntax checked.
- Local automated verification only; production deployment and real-device Flutter navigation are not performed here.
