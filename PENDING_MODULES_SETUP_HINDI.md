> Release 3: AI के लिए AI_PLANS_SETUP_HINDI.md और docs/AI_STUDIO_API.md का current contract पढ़ें।

# Growya update — installation aur pending modules setup

20 October 2026 · Release 2

Is release me pehle ke 338 API route templates ke saath **59 naye API route templates** jude hain: total **397**. Existing database/media preserve kiye gaye hain. Source package me vendor, production secrets aur runtime caches nahi hain.

## 1. Code update

Staging par database aur storage ka backup lein. Updated files merge/upload karein; apni live `.env`, `storage/app` aur uploaded media overwrite na karein.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=BusinessPermissionSeeder --force
php artisan storage:link
php artisan view:cache
```

Production par APP_ENV=production, APP_DEBUG=false aur actual HTTPS APP_URL set karein. Keys/config update ke baad `php artisan config:cache` aur `php artisan queue:restart` karein.

PHP 8.3+, cURL, OpenSSL, fileinfo, mbstring, XML aur apne DB ka PDO driver chahiye. Composer lock isi project ke saath rakhein. Public assets ZIP me hain; is update ke inline page styles ke liye npm build zaroori nahi.

## 2. Queue aur wahi existing cron

Agar pehle se cron `php artisan schedule:run` har minute chalata hai, doosra cron na banayein. Naye scheduler tasks `routes/console.php` me registered hain.

`.env`:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=300
REDIS_QUEUE_RETRY_AFTER=300
APP_TIMEZONE=Asia/Kolkata
```

Worker ko Supervisor/hosting process manager se chalayen:

```bash
php artisan queue:work --queue=notifications,ai,social,default --sleep=3 --tries=1 --timeout=240
```

Worker update ke baad `php artisan queue:restart`. Queue `retry_after` worker timeout se bada rakhein. Outbox sweeper pending AI/social/push jobs recover karta hai. AI provider call fail/timeout par local credits wapas aate hain. Social uncertain publication ko dobara automatic publish nahi kiya jata: pehle platform par check karein.

## 3. Owner/staff permissions aur pages

Business overview/sidebar se AI studio, Social publishing, Daily analytics, Shipping & tax, Accounting, Delivery confirmation aur Gateway payments milenge.

- AI: `ai.view`, `ai.use`, `ai.settings.manage`; package approval केवल super admin. Legacy manual grants package/model access नहीं देते।
- Social: `social.view`, `social.manage`.
- Shipping/tax policies: `commerce.view`, `commerce.manage`.
- Accounting: `accounting.view`, `accounting.manage`.
- Delivery aur payment permissions pehle wale alag `delivery.*`, `payments.*` hi hain.
- Admin tools: `banners.manage`, `festivals.manage`, `reports.manage`, `settings.manage`, `audit.view`.

Owner apne business ke modules dekh sakta hai. Staff ko har permission alag deni hogi. Pending/inactive business operational modules use nahi kar sakta. Seed permissions ke naam add karta hai, existing staff ko automatic naye grants nahi deta.

## 4. SMS / login OTP / delivery OTP

Default disabled. OTP API existing active account ke liye hai; naya user `/auth/register` se register hota hai. OTP authenticator 2FA bypass nahi karta.

```dotenv
GROWYA_OTP_DRIVER=http
GROWYA_SMS_URL=https://YOUR_SMS_PROVIDER/otp
GROWYA_SMS_TOKEN=
GROWYA_SMS_SENDER=
GROWYA_SMS_TEMPLATE_ID=
```

`app/Services/Extensions/SmsService.php` ka provider adapter server-owned HTTPS URL par `phone` (91 prefix), `otp`, `purpose` (`login`/`delivery`), `sender`, `template_id` bhejta hai. Apne selected provider/DLT-approved templates ke exact fields aur success acknowledgement ke hisab se adapter map karein; generic adapter ka 2xx response provider acceptance maanta hai. Agar alag templates lagte hain to purpose ke hisab se map karein. OTP kabhi response/log me nahi aata. Keys missing par 503, fake success nahi.

## 5. Firebase FCM v1

```dotenv
GROWYA_FCM_ENABLED=true
GOOGLE_APPLICATION_CREDENTIALS=/absolute/private/path/firebase-service-account.json
```

Service account file public folder se bahar rakhein. Firebase project me FCM v1 enable karein. Flutter me Firebase SDK/APNs setup ke baad `POST /api/v1/devices` karein. Token refresh par register dobara karein; logout se pehle device unregister karein. Native logout token-bound devices aur password change sab devices deactivate karta hai.

Queue notification ko devices tak bhejti hai. Provider acceptance `accepted` hai, handset delivery nahi. App ko data payload ke `delivery_id` se delivered/opened acknowledgement bhejna hai. Push at-least-once ho sakta hai; app delivery_id se duplicate display roke. Invalid UNREGISTERED token deactivate hota hai; in-app notification record bana rehta hai.

## 6. AI text/image aur wallet

```dotenv
GROWYA_AI_ENABLED=true
OPENAI_API_KEY=
GROWYA_AI_TEXT_MODEL=gpt-4.1-mini
GROWYA_AI_IMAGE_MODEL=gpt-image-1
```

AI generation अब approved package snapshot के models/rates से चलता है। Owner package request और super admin approval flow AI_PLANS_SETUP_HINDI.md में है। पुराना wallet grant नए model entitlement की जगह नहीं लेता। Text और image generation supported हैं; video नहीं।

## 7. Facebook / Instagram

```dotenv
META_APP_ID=
META_APP_SECRET=
META_GRAPH_VERSION=
META_REDIRECT_URI=https://YOUR_DOMAIN/integrations/meta/callback
```

Meta app ke supported Graph version, exact callback URL aur approved permissions configure karein. Page-management/content-publish permissions aur eligible professional Instagram account chahiye. `APP_URL` aur public storage URL HTTPS hon, taaki platform image fetch kar sake.

Connect → Meta authorization → app me page list → selected Facebook Page/linked Instagram account. Tokens encrypted hain aur API se expose nahi hote. Revoked/expired access par reconnect karein; page token ke liye fake refresh token issue nahi hota.

Facebook: text ya one-image post. Instagram: one JPEG image + caption. Draft/now/scheduled, cancellation before publication, per-account results aur daily/weekly recurring posts supported. Festival calendar se caption choose kar sakte hain. Missed recurring runs downtime ke baad ek baar run hote hain; missed days ka flood nahi hota. Video/reels/carousels, Google Business/YouTube/X publishing is release me nahi hain. Provider approval/live posting acceptance abhi actual keys ke saath karni hai.

## 8. Shipping/GST, Razorpay, delivery

Shipping & tax page par flat shipping, optional free-shipping threshold, one exclusive GST rate aur seller state configure karein. GST default off hai; valid-format GSTIN required. Rates user configure karta hai. System discount ke baad merchandise + shipping par tax lagata hai; intra-state CGST/SGST, inter-state IGST snapshot store hota hai. Existing orders ke totals mutate nahi hote.

`POST /shops/{business}/quote` se exact total lein; checkout me `quoted_total_paise` bhejne par changed amount reject hota hai. Web checkout bhi final total confirm karata hai. Yeh single-rate engine hai: per-item HSN/multiple rates, inclusive pricing, e-invoice/returns filing automate nahi ki gayi.

```dotenv
RAZORPAY_ENABLED=true
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
```

Razorpay webhook URL `https://YOUR_DOMAIN/api/v1/webhooks/razorpay`, event `payment.captured`. Pehle test keys par exact-amount/captured verification aur duplicate webhook test karein. User browser/mobile success ko akela paid nahi maana jata. Server provider payment fetch karke verify karta hai. Unknown create response par same request ko blindly repeat na karein; receipt-based reconciliation hai. Unsettled gateway attempt cancellation block karta hai; provider-side unsuccessful/unused attempts ko operator se reconcile karayein. Actual provider refund abhi Razorpay operator se manually karein; app ke old refund records gateway API refund execute nahi karte.

Delivery policy on hone par order delivered mark karne se pehle OTP/proof mandatory. Naye confirmation aur purane status/tracking actions dono me same guard hai. Photo private hai; authorized customer/seller hi download kar sakta hai.

## 9. Accounting

Accounts banayein, cash/bank accounts ko mark karein, balanced journal post karein. Opening balances bhi balanced opening journal se enter karein. Posted entries edit/delete nahi hoti; reversal karein. Trial balance, income/expense, profit aur cash movement reports available hain. Yeh manual double-entry journal hai; existing income/expense ledger ya order/payment records automatic journal me post nahi hote. Dono reports ko jodkar double count na karein. Bank feeds, tax filing, depreciation/accrual rules aur automated reconciliation alag extensions hain.

## 10. Test aur API files

`docs/VALIDATION.md` me exact test results/limits. `docs/api-v2/` me searchable full reference, 397-request Postman collection, environment aur Flutter transport sample hai. `docs/NEW_MODULES_API.md` me naye flows/response contracts hain. Flutter sample transport hai; SDK-compiled complete app nahi.

Actual keys/app review, live MySQL locking aur real devices ke saath staging acceptance baki hai. Koi live deployment, real payment, SMS ya social post is code-update session se nahi bheja gaya.
