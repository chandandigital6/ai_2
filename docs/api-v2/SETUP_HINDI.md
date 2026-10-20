# Growya AI Plans & Brand Studio — Setup

## Update existing project

1. Database और files का backup लें। ZIP का code अपने project में copy करें। अपनी server `.env`, database और uploaded media को सुरक्षित रखें; ZIP से उन्हें overwrite न करें। Existing `APP_KEY` न बदलें।
2. Project root में चलाएँ:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=BusinessPermissionSeeder --force
php artisan db:seed --class=AiPlanSeeder --force
php artisan permission:cache-reset
```

`migrate:fresh` न चलाएँ। नई migration additive है। AiPlanSeeder तीन **inactive draft** examples बनाता है; आपकी existing plan settings overwrite नहीं करता। Server की current database/media इस update में delete नहीं होती। ZIP में `.env`, credentials, database dumps और vendor शामिल नहीं हैं। Existing build files शामिल हैं; नई AI screen के लिए npm build जरूरी नहीं है।

Fresh installation: `.env.example` से `.env` बनाएँ; DB/mail/APP_URL configure करें, केवल fresh install पर `php artisan key:generate`, फिर migrations और `php artisan db:seed --force`। Super admin account अपनी existing provisioning process से बनाएँ; यह seeder privileged demo passwords नहीं बनाता।

## AI provider और workers

Server `.env` में:

```dotenv
GROWYA_AI_ENABLED=true
OPENAI_API_KEY=YOUR_SERVER_SIDE_OPENAI_API_KEY
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=300
REDIS_QUEUE_RETRY_AFTER=300
```

API key केवल server पर रखें। Flutter/frontend को key न दें। Models तक access और OpenAI API billing अलग से active होने चाहिए; app के internal credits provider balance नहीं हैं।

```bash
php artisan config:cache
php artisan queue:restart
php artisan queue:work --queue=notifications,ai,social,default --timeout=240 --tries=1
```

Worker को Supervisor/systemd/hosting process manager से लगातार चलाएँ। Job timeout 210 sec और provider request timeout 180 sec है। Queue retry_after / visibility timeout worker timeout से अधिक रखें, कम से कम 300 sec। `sync` queue production generation के लिए न रखें। PHP CLI memory_limit कम से कम 512M recommended है। Web uploads के लिए upload_max_filesize >= 5M और post_max_size >= 8M रखें।

Cron हर minute:

```cron
* * * * * cd /YOUR_PROJECT && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler abandoned processing jobs (6 min) और queued jobs (30 min) fail करके credit refund करता है। Manual recovery: `php artisan growya:ai-recover`। Refund केवल एक बार होता है; expired package में refund आने से उसकी validity extend नहीं होती।

## Super admin: plans कहाँ बनाएँ

Sidebar → **AI plans & approvals**, या `/admin/ai-plans`।

- Name, selling price **₹ rupees**, credits और approval के बाद validity days भरें।
- Caption model और image model चुनें। किसी type को शामिल नहीं करना तो “Not included” रखें।
- Caption/image का per-generation credit cost तय करें। Image quality फिलहाल medium, output PNG है।
- Saved prompts की maximum संख्या और reference images की limit रखें; एक logo extra है।
- Status **Active** करके save करें।

Draft examples: Starter (300 credits / 3 prompts / 2 references), Business (1000 / 10 / 5), Premium (2500 / 30 / 10)। ये retail recommendations नहीं हैं; कीमत 0 और status inactive रहता है जब तक आप अपनी कीमत set करके activate न करें। Package में दिख रहे models ही generation में इस्तेमाल होते हैं।

## Owner का flow

Business खोलें → **AI studio** (`/businesses/BUSINESS_ID/workspace/ai`)।

1. **Packages:** plan की कीमत/model/credits देखे, optional payment reference और message देकर request भेजे। एक समय में एक pending request।
2. Super admin requests में payment/reference manually verify करे और **Approve & grant credits** चुने। Reject करने पर कारण जरूरी है। इस flow में automatic payment charge/renewal नहीं होता।
3. Approval से package active होता है। Validity approval time से शुरू। Repeat approval credits दोबारा नहीं देता।
4. **Brand setup:** नाम, logo, colours, contact/address, language, default image size और poster instructions save करे। Logo/profile purchase से पहले save हो सकता है। References और new prompts के लिए active package allowance जरूरी है।
5. **Saved prompts:** Daily jewellery, festivals, offers, captions के अलग prompts बनाए। प्रत्येक में generation type, language, size, avoid instructions और चुनी reference images रख सकता है। Edit/delete उपलब्ध है।
6. **Generate:** approved package, saved prompt/custom brief और current occasion चुने। Default references इस्तेमाल करे या advanced selection में केवल चुनी images दे। Logo अपने आप शामिल होता है।
7. Result उसी screen पर अपने आप आता है; image preview/download, caption copy और history उपलब्ध हैं। बार-बार Generate न दबाएँ। Provider जितना समय लेता है उतना wait जरूरी है; “तुरंत” का अर्थ automatic UI update है।

Fresh variation toggle हर नई generation में अलग variation brief देता है। यह automatic daily scheduler नहीं है। AI से बिल्कुल अलग design या logo/product की pixel-perfect copy की guarantee नहीं; print/publish से पहले result देखें। Sizes: square 1024×1024, portrait 1024×1536, landscape 1536×1024। ये 4:5 या 9:16 नहीं हैं।

## Credits और limits

- प्रत्येक approval का अलग credit bucket है; selected bucket के model और rate लागू होते हैं। सस्ते plan credits से premium model नहीं चल सकता।
- पुराने manually granted wallet credits database में सुरक्षित हैं, लेकिन नए generation के लिए approved package जरूरी है। Manual legacy grant API model access नहीं देता। Transition में owner से package request लेकर approve करें।
- Multiple active packages हों तो saved prompt/reference library limit **highest active allowance** है, जोड़कर नहीं। Generation references पर selected package की limit लागू है। Logo extra है।
- Expired credits usable balance में नहीं आते। New purchase expiry/credit balance अलग है; पुराने bucket को reset नहीं करती।
- Expired/downgraded package पर पुरानी prompts/assets नहीं मिटतीं; new prompt/upload limits active allowance से तय होते हैं। Smaller package के लिए कम references चुने।
- Failed generation पर उसी package और aggregate ledger में once-only refund होता है। Wallet history में पुराने/expired balance भी रह सकते हैं; usable balance `entitlements.available_credits` देखें।
- दो jobs business में एक साथ pending/processing रह सकती हैं। Named rate limit 10 generation requests/minute/user है; duplicate click रोकने के लिए button disable होता है।
- Image uploads PNG/JPG/WebP, अधिकतम 5 MB प्रति image, dimensions <=8192×8192। Selected images + logo कुल 20 MB तक।
- Deleted/replaced assets future selection से हटते हैं; queued generation के immutable references के लिए private original bytes retained हैं। Public storage link नहीं बनता। Retention cleanup business policy के अनुसार बाद में करें; queued input की files न हटाएँ।

## Permissions

| काम | Access |
|---|---|
| Plan create/edit और request approve/reject | केवल active super_admin |
| Package request | संबंधित approved business का owner |
| AI results, plan list, wallet, private images | business-scoped `ai.view` |
| Generation | business-scoped `ai.use` |
| Brand/logo/references/saved prompts manage | business-scoped `ai.settings.manage` |

Owner को अपने business में access है; staff को अलग permissions दें। Staff को result देखने और generate करने के लिए ai.view + ai.use दोनों दें। Settings बदलवाने हों तो ai.settings.manage भी दें। Global staff role किसी दूसरे business का access नहीं देता।

## Error handling

| Message/code | अगला कदम |
|---|---|
| app_rate_limit / HTTP 429 | Retry-After के बाद retry; app की rate limit |
| provider_rate_limit | Provider busy; minute बाद नई request |
| provider_quota | Super admin provider billing/quota जाँचे |
| provider_auth | Server API key जाँचे |
| model_unavailable | Selected model का provider account access जाँचे |
| provider_timeout / worker_timeout | Provider/worker जाँचे; failed job का credit वापस आता है |
| HTTP 422 | Form के पास validation error देखें; plan/reference/credit allowance ठीक करें |
| HTTP 419/401 | Reload/login करें |
| HTTP 403 | Owner से business permissions जाँचवाएँ |

Raw API key/provider response UI पर नहीं दिखते। App log में sanitized provider code/request id और generation id मिलता है। Server `.env` में `APP_DEBUG=false` रखें।

API functions बदलने के लिए `docs/API_FUNCTIONS_GUIDE_HINDI.md` और Flutter integration के लिए `docs/AI_STUDIO_API.md` पढ़ें।
