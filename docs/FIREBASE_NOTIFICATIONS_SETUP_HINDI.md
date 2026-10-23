# Growya Firebase notifications setup

23 October 2026

## Is update mein kya fix hua

- Desktop alert click pe bell menu khulne ke badle exact chat / order / enquiry khulta hai.
- Browser Firebase token registration, foreground push, background service worker aur notification click routing add hai.
- My notifications mein Enable browser alerts, Send me a test, Disable this browser aur recent delivery status hain.
- Chat page live refresh karta hai; foreground push aate hi current conversation refresh hoti hai. Network error chupne ke badle connection status dikhata hai.
- Customer message owner aur authorized assigned staff ko alert karta hai; business reply customer ko. Sender ko apni hi chat notification nahin jaati.
- New order aur product/service enquiry owner ko notification + registered devices par push queue karte hain. Customer order status alerts existing flow mein hain.
- Web logout par current browser ka device mapping deactivate hota hai. Native app token endpoints unchanged hain.

Code ready hai; Firebase project credentials / Web App config is request mein provide nahin hui. Live Firebase/device delivery aur production deployment verify nahin hue. Tests HTTP/Firebase/browser mocks use karte hain.

## 1. Sahi JSON pehchanen

Server ko **service-account JSON** chahiye: Firebase Console → Project settings → Service accounts → Generate new private key. Ismein `type: service_account`, `project_id`, `client_email`, `private_key` hote hain.

`google-services.json` Android client ki file hai. Ye Laravel server credentials ki jagah kaam nahin karegi. Browser web configuration aur public VAPID key bhi service-account JSON se alag hain. Private key ko chat, Flutter assets ya public folder mein paste/upload na karen.

## 2. Hosting par private file rakhen

Apne Hostinger account ke home folder mein, `public_html` ke bahar folder banayen:

```bash
mkdir -p /home/u179997197/groya-private
chmod 700 /home/u179997197/groya-private
```

SFTP/File Manager se downloaded service-account file ko is naam/path par upload karen:

```text
/home/u179997197/groya-private/firebase-service-account.json
```

Upload ke baad:

```bash
chmod 600 /home/u179997197/groya-private/firebase-service-account.json
```

PHP ko file readable honi chahiye. Hosting user alag ho to owner/group permissions accordingly set karen. Default code fallback `storage/app/private/firebase-service-account.json` bhi support karta hai, jab project ka web document root strictly `public/` ho. Public web root ke bahar ka explicit path hosting par use karen.

## 3. Firebase Web App aur VAPID public key

Same Firebase project mein Project settings → General → Your apps → Web app (`</>`) register/select karen. SDK configuration se apiKey, authDomain, projectId, messagingSenderId, appId copy karen.

Project settings → Cloud Messaging → Web Push certificates mein key pair generate/select karke **public VAPID key** copy karen. Cloud Messaging API (HTTP v1) enabled ho aur service account ko project par messaging send permission ho.

Server `.env` mein ye values fill karen. Existing matching keys ko update karen, duplicate lines na banayen:

```dotenv
APP_URL=https://groya.in
QUEUE_CONNECTION=database

GROWYA_FCM_ENABLED=true
GOOGLE_APPLICATION_CREDENTIALS=/home/u179997197/groya-private/firebase-service-account.json

GROWYA_FCM_WEB_ENABLED=true
FIREBASE_WEB_API_KEY=PASTE_WEB_APP_API_KEY
FIREBASE_WEB_AUTH_DOMAIN=YOUR_PROJECT.firebaseapp.com
FIREBASE_WEB_PROJECT_ID=YOUR_PROJECT_ID
FIREBASE_WEB_MESSAGING_SENDER_ID=YOUR_SENDER_ID
FIREBASE_WEB_APP_ID=YOUR_WEB_APP_ID
FIREBASE_WEB_VAPID_KEY=PASTE_PUBLIC_VAPID_KEY
```

Browser config, VAPID key, native Firebase config aur service-account project same project ke hon. `private_key` ko FIREBASE_WEB_VAPID_KEY mein mat dalen. Database queue tables existing migrations mein hain; Redis pe pehle se working queue ho to existing `QUEUE_CONNECTION` retain karen.

## 4. Updated files apply aur worker

`docs/FIREBASE_NOTIFICATIONS_CHANGED_FILES.json` mein is update ke exact changed/new files hain. Matching project-root paths par copy/merge karen. Server `.env`, uploads aur actual private credential file preserve karen. ZIP ke existing `.env` se server settings overwrite na karen.

Project root mein:

```bash
php artisan optimize:clear
php artisan queue:restart
php artisan growya:push-check
```

Doctor command credentials ko print nahin karta, Firebase se connect nahin karta aur notification nahin bhejta. Ismein FCM enabled, browser config readiness, file/key validity, project match aur device/queue counts dikhte hain. `NO`, `INCOMPLETE`, `MISMATCH` ya missing file ko pehle sahi karen.

Worker foreground test:

```bash
php artisan queue:work --queue=notifications,ai,social,default --timeout=240 --tries=4
```

`INFO Processing jobs` ka matlab worker waiting hai. Actual push par `App\Jobs\Extensions\SendPush` job process honi chahiye. Purane `--tries=1` ke badle `--tries=4` use karen taaki transient FCM failures retry hon. Production mein worker ko Supervisor/hosting process manager se continuously run karen.

Agar hosting long-running processes allow nahin karti, existing hosting cron mein minute-wise bounded worker chal sakta hai (host ki allowed cron frequency ke mutabik):

```bash
cd /home/u179997197/domains/groya.in/public_html && /usr/bin/flock -n /home/u179997197/groya-private/groya-queue.lock /usr/bin/php artisan queue:work --queue=notifications,ai,social,default --timeout=240 --tries=4 --stop-when-empty --max-time=50
```

PHP/flock executable paths host par verify karen. Continuously running worker aur same queue ke duplicate cron ko saath enable na karen. Minute-wise cron delivery instant nahin hogi. Existing scheduler `php artisan schedule:run` cron retain karen; outbox recovery scheduler mein hai.

Is update ke liye naya migration, seeder, npm build ya Composer dependency nahin chahiye. Agar installation purani hai aur `user_devices` / `growya_push_deliveries` missing hain, latest project's pehle se pending migrations ka status `php artisan migrate:status` se check karen.

## 5. Browser par exact test

1. HTTPS par `https://groya.in` sign in karen.
2. My notifications (`/my-notifications`) kholen → Enable browser alerts → browser popup mein Allow.
3. `Browser alerts enabled` ke baad Send me a test click karen.
4. Recent delivery status check karen. `queued` = worker waiting; `accepted` = Firebase ne request accept ki, device receipt confirm nahin; `opened` = notification kholi gayi.
5. Dusre browser/incognito/device mein customer login karke business ko message bhejen. Owner ko alert aana chahiye; click se `/messages/{thread}` khulna chahiye. Owner reply customer ko alert karega, jab customer ne apne browser/app mein notifications enable ki hon.
6. Owner tab background/minimize karke test repeat karen. Browser process aur OS notification permission/background delivery enabled honi chahiye. Browser force-quit/device offline par immediate delivery guarantee nahin hai.
7. New customer order, product enquiry aur service enquiry banakar owner alert par click karen. Correct authorized detail screen khulni chahiye.
8. Current browser logout karke test karen: us session ka device inactive hona chahiye.

Chat page khuli ho to incoming message turant push event se refresh hota hai; fallback poll 8 seconds par hai. Current visible chat ke liye duplicate desktop popup suppress hota hai. Owner ke dashboard/bell alerts Firebase disable hone par bhi existing polling se kaam karte hain.

## 6. Common issues

| Status/problem | Kya check karen |
| --- | --- |
| Dashboard alert hai, OS push nahin | Enable browser alerts, browser permission, Web App config/VAPID, active device token, worker |
| queued hi reh raha | Notifications queue worker/process/cron run hai ya nahin |
| TRANSPORT_OR_CONFIG / RETRY_EXHAUSTED | Credentials path/read permission, outbound HTTPS/cURL/OpenSSL; doctor command run karen. Fix ke baad Send me a test ka naya alert bhejen. |
| SENDER_ID_MISMATCH / PERMISSION_DENIED | Same Firebase project ke Web/native settings aur service-account authorization |
| UNREGISTERED | Token expire/revoke ho gaya; server inactive karta hai. Browser mein Enable browser alerts dobara karen. |
| Permission denied | Browser site settings mein Notifications Allow; OS settings/Do Not Disturb bhi check karen |
| /firebase-messaging-sw.js 404 | Updated route deployed ho; `optimize:clear`; document root Laravel `public/`; static proxy fallback `.js` request Laravel tak jaane de. Is path par stale physical file ho to new Laravel route ke saath conflict resolve karen. |
| Worker import/network blocked | Browser CSP/firewall ko `www.gstatic.com`, Firebase/Google Messaging endpoints allow karne honge |
| iPhone/iPad web push unsupported | Supported iOS/iPadOS par site ko Home Screen par add karke installed web app se enable karen |
| Alert khulta hai par 403 | Notification se target permission bypass nahin hoti; current staff grant/assignment check karen |

## 7. Localhost test

Private JSON project/public ke bahar rakhen. Windows `.env` path mein forward slashes use karen, jaise `GOOGLE_APPLICATION_CREDENTIALS=D:/Growya_Private/firebase-service-account.json`. APP_URL local server ke actual URL par set karen. Same Firebase project ke Web config aur VAPID fill karke `optimize:clear`, doctor aur queue worker run karen.

`http://localhost` / loopback browser secure-context exception se supported hai; normal LAN `http://192.168...` par HTTPS chahiye. Dono accounts ko alag browser profiles mein login karke message test karen. Internet chahiye: FCM cloud service hai. Sirf JSON download kar lene se browser token/permission automatically set nahin hoti.

## 8. Flutter developer ke liye

Server service-account JSON Flutter app/assets mein nahin jayegi. App ko apne Firebase native client configuration (Android google-services.json / iOS GoogleService-Info.plist) chahiye.

Existing bearer API lifecycle:

- Login/FCM token refresh ke baad `POST /api/v1/devices` with `{fcm_token, platform: android|ios, app_version}`.
- Logout se pehle `DELETE /api/v1/devices/{device}`; existing backend logout token-bound mappings revoke karta hai.
- Push data keys ab `type`, `business_id`, `recipient_id`, `delivery_id`, `action_url`, `title`, `body`; relevant case mein `thread_id`, `order_id`, `enquiry_id` bhi hain. Sab data values strings hain.
- Foreground `FirebaseMessaging.onMessage`, background/open `onMessageOpenedApp`, terminated launch `getInitialMessage()` handle karen. `thread_id` par conversation screen, `order_id` par role-appropriate order screen aur enquiry type+ID par relevant detail kholen. Target API ka authorization retain karen.
- Delivery/open acknowledgement: `POST /api/v1/push-deliveries/{delivery}/ack` with `{event: delivered|opened}`. FCM acceptance ko actual delivered/opened na manen.
- Android notification runtime permission/channel aur iOS APNs setup app side chahiye. Is ZIP mein Flutter source modify nahin hua; yeh Laravel project hai.

## Main code locations

| Kaam | File |
| --- | --- |
| Firebase OAuth / HTTP v1 transport | app/Services/Extensions/FcmService.php |
| Push data + native/web payload routing | app/Jobs/Extensions/SendPush.php |
| Web settings whitelist | app/Services/Extensions/WebPushConfig.php |
| Browser registration / self-test / worker route action | app/Http/Controllers/GrowyaWebPushController.php |
| Browser permission/token/foreground/status | public/growya-ui/push.js |
| Background display + click | resources/views/notifications/worker.blade.php |
| Bell click + feed | resources/views/components/growya-live-notification-bell.blade.php |
| Owned notification redirect | app/Http/Controllers/GrowyaNotificationController.php |
| Live chat refresh | public/growya-ui/chat.js |
| Server diagnostics | app/Console/Commands/GrowyaPushCheck.php |

Firebase reference docs:
- https://firebase.google.com/docs/cloud-messaging/auth-server
- https://firebase.google.com/docs/cloud-messaging/js/client
- https://firebase.google.com/docs/cloud-messaging/js/receive
- https://firebase.google.com/docs/web/alt-setup

Automated results: `docs/FIREBASE_NOTIFICATIONS_VALIDATION.json`.
