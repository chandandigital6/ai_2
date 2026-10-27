# Groya API code kahan hai — 26 October 2026

Ab har API ka apna explicit controller function hai. Purana `ActionController` routing aur `routes/api_actions.php` remove kar diye gaye hain.

## Kaunsi file kholni hai?

| Kaam | File |
|---|---|
| Login/register/profile/users/location/permissions routes | routes/api.php |
| Baaki 399 feature routes | routes/api/v1/routes.php |
| Product API functions | app/Http/Controllers/Api/V1/ProductController.php |
| Product variants API functions | app/Http/Controllers/Api/V1/ProductVariantController.php |
| Chat API functions | app/Http/Controllers/Api/V1/ChatController.php |
| Cart/checkout API functions | app/Http/Controllers/Api/V1/CartCheckoutController.php |
| Marketplace API functions | app/Http/Controllers/Api/V1/PublicMarketplaceController.php |
| Login/register API functions | app/Http/Controllers/Api/V1/AuthController.php |
| User/profile/permissions API functions | app/Http/Controllers/Api/V1/AccountController.php |
| AI/social/extension API functions | app/Http/Controllers/Api/V1/Extensions/ |
| Common binding/authorization/JSON transport service | app/Services/Api/ApiActionService.php |
| Complete 411 endpoints ki mapping | docs/API_ROUTE_CONTROLLER_MAP.md |
| Postman import | docs/Growya_API.postman_collection.json |

`/api/user` legacy endpoint v1 collection ke bahar hai; v1 mein 411 endpoints hain.

## Ab flow kya hai?

Example: POST /api/v1/businesses/{business}/products

1. `routes/api.php` /api/v1 prefix set karta hai aur explicit routes file load karta hai.
2. `routes/api/v1/routes.php` seedha `Api/V1/ProductController@store` register karta hai.
3. `app/Http/Controllers/Api/V1/ProductController.php` ka `store()` API entry point hai.
4. Method `ApiActionService` ko known shared operation `App/Http/Controllers/ProductController@store` deta hai.
5. Shared operation existing services/validation/database work karti hai; API service HTML view/redirect ko existing JSON contract mein adapt karti hai.

AI/social controllers jo pehle se JSON/file responses dete the, unka output `adaptResponse: false` ke saath pass through hota hai, isliye extra data wrapping nahi hoti.

**Boundary clear rakhein:** Separate API entry points aur common transport service banaye hain. Saari business logic ko domain-specific service classes mein extract nahi kiya hai. Existing domain controllers/services shared source hain. API file mein API-only changes independent kar sakte hain; shared operation ki validation/query badlenge to web bhi affected hoga. Shared operation ka source har API method ke comment aur Postman description mein hai.

## Sirf API response change karna ho

API controller file mein method edit karein, web controller mein nahi. Example, marketplace JSON response mein optional metadata add karna:

```php
public function index(Request $request)
{
    $response = $this->actions->respond($request, DomainController::class, 'index');
    $payload = $response->getData(true);
    $payload['api_version'] = 'v1';
    $response->setData($payload);

    return $response;
}
```

Yeh example marketplace jaise JSON-returning method ke liye hai; PDF/download/file endpoints par `getData()` use na karein. Existing `data`, pagination nesting, status codes aur error contracts Flutter ke saath coordinate karke hi badlein. Shared database/validation rules existing domain operation ya uske business service mein badlein.

## Naya API route add karna ho

`routes/api/v1/routes.php` mein explicit route add karein:

```php
Route::get('your-path', [\App\Http\Controllers\Api\V1\YourController::class, 'index'])
    ->middleware(['auth:sanctum', '\App\Http\Middleware\RequireGrowyaToken', 'throttle:120,1'])
    ->name('api.v1.your-module.index');
```

New API controller mein `index()` implement karein. Public discovery route ke liye auth tabhi remove karein jab response genuinely public ho. Business/tenant record ko authorize karna zaroori hai. Shared adapter use karne par route parameter names ko shared function parameter names se match rakhein, jaise `{business}`, `{product}`.

`routes/extension_actions.php` ab **web extension routes** ke liye retained hai; API routes ko explicit new file mein edit karein. API-only route edits web route registry ko automatically update nahi karti hain.

## Postman mein ek-ek API kaise test karein?

1. Postman → Import → `docs/Growya_API.postman_collection.json`.
2. Collection → Variables: `base_url=https://groya.in/api/v1`, `email`, `password` apne actual values set karein. Local/staging domain bhi set kar sakte hain.
3. Collection ke `auth-accounts` folder mein `POST /auth/login` kholein aur Send karein. Success par login script token ko collection variable `token` mein save karta hai. Environment mein same-name `token` ki stale/blank value ho to remove/update karein, kyunki environment collection ko override kar sakta hai.
4. `GET /auth/me` chalayein. Current user sahi aaye to token setup done.
5. Public browsing `discovery` folder mein No Auth hai; protected requests bearer token use karti hain. Admin requests ke liye `super_admin_token` variable alag fill karein. `/users` jaise super-admin actions par bhi correct admin token use karein.
6. `{business}`, `{product}`, `{thread}` ke variables mein actual IDs set karein. `{slug}` etc mein actual slug chahiye. Request ki Description mein exact API/shared function path milega.
7. Body → raw JSON ya existing file/form-data example dekhein. `BODY NEEDS INPUT` wali requests mein valid required fields bhare bina `{}` send na karein. Complete field contracts `docs/api-v2/Full_API_Reference.md` aur shared function ki validation mein hain. Collection route-complete hai; every endpoint ki success payload fixture ready hone ka claim nahi hai.
8. Ek request test karke status/body dekhein. Live par poori collection runner se mat chalayein: publish, payments, delete aur enquiry requests real effects karti hain.

Status: 200/201 success; 401 login; 403 permission; 404 wrong/missing record; 405 wrong HTTP method; 422 validation; 429 Retry-After; 500 server log.

## Server par function dhoondhna

```bash
php artisan route:list --path=api/v1/messages
php artisan route:list --path=api/v1/businesses
```

Ab output mein `Api\V1\ChatController@send` jaisa actual API function aayega. Editor mein us controller ko kholkar method search kar sakte hain.

## Deploy

Previous 429/guest/account-type fixes included hain. Current .env, Firebase credentials, database aur user uploads preserve rakhein.

Is update mein purani files delete ki gayi hain:

- routes/api_actions.php
- app/Http/Controllers/Api/V1/ActionController.php

Version-control deployment mein removals apply hongi. Agar ZIP ko manually existing project par copy kar rahe hain, references/custom code check karke in purani files ko remove karein; built-in routes ab inhe use nahi karti hain.

```bash
composer dump-autoload
php artisan optimize:clear
php artisan queue:restart
```

No migration/seeder/npm build required. `migrate:fresh` mat chalayein. Existing routes/response formats stable hain; Flutter ke endpoint URLs change karne ki zaroorat nahi. Production par representative GET/login/enquiry/chat flows apne accounts se verify karein.
