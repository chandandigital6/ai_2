# API function कहाँ हैं और कैसे बदलें

Growya में दो route paths हैं। दोनों की business logic actual controllers में है। हर API के लिए अलग duplicate function जरूरी नहीं है।

## 1. नए AI Studio APIs — सीधे controller methods

`routes/extension_actions.php` की एक entry:

```php
['POST', 'businesses/{business}/ai', E\AiController::class.'@store', 'ai.store', false, true, 'ai-generation'],
```

इसका अर्थ:

- API: `POST /api/v1/businesses/{business}/ai`
- Web: `POST /businesses/{business}/ai`
- Function: `app/Http/Controllers/Extensions/AiController.php` में `store()`
- `routes/api.php` और `routes/extensions_web.php` इसी registry से routes register करते हैं।
- Generation payload/validation/debit बदलना → `AiController::store()`।
- Poll response बदलना → `AiController::show()` और `generationData()`।

| बदलाव | File / function |
|---|---|
| AI plan list/request/create/edit/approval | `app/Http/Controllers/Extensions/AiPlanController.php`: catalog, requestPlan, store, update, review |
| Package validity, model/credit allowance | `app/Services/Extensions/AiPlanService.php`: active, entitlements, pack |
| Brand profile, asset upload/read/delete, preset CRUD | `app/Http/Controllers/Extensions/AiBrandController.php` |
| Saved prompt + brand + logo/reference composition | `app/Services/Extensions/AiPromptBuilder.php`: build |
| OpenAI endpoint/body/model/image references | `app/Services/Extensions/AiProvider.php`: generate, check |
| Background generation and completion | `app/Jobs/Extensions/GenerateAi.php`: handle |
| Credit refunds and public errors | `app/Services/Extensions/AiFailureService.php`: fail |
| Aggregate credit ledger | `app/Services/Extensions/AiWalletService.php`: change, balance |
| Abandoned worker recovery | `app/Console/Commands/RecoverAiGenerations.php` |
| AI supported model allowlist / sizes / concurrency | `config/growya_ai.php` |
| Provider key and enabled flag | `config/growya_extensions.php` → server .env |
| Generation rate limiter | `app/Providers/AppServiceProvider.php`: named ai-generation |
| Owner screen and admin screen | `resources/views/extensions/sections/ai.blade.php`, `resources/views/extensions/ai-admin.blade.php` |
| Toasts, polling, result rendering, submit buttons | `public/js/growya-ai.js` |
| AI permissions available for staff | `config/business_permissions.php` + BusinessPermissionSeeder |

Backend client से supplied model/credits स्वीकार नहीं करता। Models, cost या quotas बदलते समय package snapshots और credit/refund rules bypass न करें। Existing approved purchase original terms रखती है; plan edit केवल नई requests पर लागू है। New model allowlist में जोड़ने से पहले provider API parameters compatibility verify करें।

## 2. पुराने modules — shared JSON adapter

`routes/api_actions.php` में entries में पूरा controller@method मिलता है। उदाहरण:

```php
['GET', '...', 'App\\Http\\Controllers\\SomeController@index', 'api.v1....', [...]],
```

`routes/api.php` इन entries को `app/Http/Controllers/Api/V1/ActionController.php::__invoke()` से चलाता है। Adapter route की `domain_action` पढ़कर actual controller method call करता है। Controller view देता है तो adapter data को JSON बनाता है; redirect देता है तो JSON message बनाता है।

इसलिए Products API बदलना हो तो पहले `routes/api_actions.php` में ProductController की entry खोजें और उसी actual method में बदलाव करें। Adapter की हर request के लिए shared `__invoke()` में module-specific logic न डालें। Shared web controller बदलने से web और API दोनों पर असर हो सकता है।

## 3. Account/auth APIs

`routes/api.php` में सीधे mapped हैं:

- `app/Http/Controllers/Api/V1/AuthController.php` — register, login, me, logout।
- `app/Http/Controllers/Api/V1/AccountController.php` — users/profile/password/master location APIs।

## 4. किसी API का function जल्दी ढूँढें

```bash
php artisan route:list --path=api/v1/businesses
rg -n "ProductController|products" routes/api_actions.php
rg -n "AiController|ai/" routes/extension_actions.php
```

Function बदलें → validation और permission intact रखें → संबंधित feature test चलाएँ → `php artisan optimize:clear` → long-running worker हो तो `php artisan queue:restart`। Endpoint/body बदले तो docs और Postman भी update करें।

New AI tests:

```bash
php artisan test --filter=AiPlanStudioTest
```

`docs/AI_STUDIO_API.md` new AI contract का authoritative reference है। बाकी module API documentation `docs/api-v2/` में है।
