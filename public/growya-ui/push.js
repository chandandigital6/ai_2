(function () {
    'use strict';
    if (window.GrowyaPush) { window.GrowyaPush.refresh(); return; }
    let settings, registration, messaging, sdk, unsubscribe, ready = false, busy, checkedAt = 0;
    let toastTimer;
    const readSettings = () => {
        const node = document.getElementById('growya-push-settings');
        if (!node) return null;
        try { return JSON.parse(node.textContent); } catch (_) { return null; }
    };
    settings = readSettings();
    if (!settings) return;
    const optKey = () => 'growya-push-off-' + settings.userId;
    const optedOut = () => { try { return localStorage.getItem(optKey()) === 'yes'; } catch (_) { return false; } };
    function say(message, error = false, notify = false) {
        document.querySelectorAll('[data-push-status]').forEach(node => { node.textContent = message; node.style.color = error ? '#b03f38' : ''; });
        document.dispatchEvent(new CustomEvent('growya:push-status', {detail: {message, error}}));
        if (!notify) return;
        let toast = document.getElementById('growya-push-toast');
        if (!toast) {
            toast = document.createElement('div'); toast.id = 'growya-push-toast'; toast.setAttribute('role', 'status');
            toast.style.cssText = 'position:fixed;bottom:24px;right:20px;z-index:9999;max-width:calc(100vw - 40px);padding:15px 20px;border-radius:12px;color:white;box-shadow:0 8px 30px #0002;font:14px/1.6 system-ui';
            document.body.append(toast);
        }
        toast.style.background = error ? '#8f392f' : '#17573e'; toast.textContent = message; toast.hidden = false;
        clearTimeout(toastTimer); toastTimer = setTimeout(() => { toast.hidden = true; }, 6500);
    }
    async function request(url, data) {
        const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(url, {method: data === undefined ? 'GET' : 'POST', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': settings.csrf}, body: data === undefined ? undefined : JSON.stringify(data)});
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw Error(result.message || (response.status === 419 ? 'Session expired. Reload this page and try again.' : 'Unable to update notifications. Please try again.'));
            return result;
        } finally { clearTimeout(timer); }
    }
    const supported = () => window.isSecureContext && 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
    function renderDeliveries(config) {
        const root = document.querySelector('[data-push-deliveries]'); if (!root) return;
        root.replaceChildren();
        const labels = {queued: 'Waiting for the notifications worker', retry: 'Retry scheduled', accepted: 'Firebase accepted; device receipt is not yet confirmed', delivered: 'Device acknowledged receipt', opened: 'Opened', failed: 'Failed', skipped: 'Skipped (device or account unavailable)'};
        for (const delivery of config.recent_deliveries || []) {
            const line = document.createElement('p');
            line.textContent = 'Alert #' + delivery.id + ': ' + (labels[delivery.status] || delivery.status) + (delivery.error_code ? ' · ' + delivery.error_code : '');
            root.append(line);
        }
    }
    async function connect(config) {
        if (busy) return busy;
        busy = (async () => {
            ready = false;
            if (!supported()) throw Error('Browser alerts need HTTPS and a browser that supports web push.');
            if (!config.enabled) throw Error('Firebase browser setup is incomplete. Add the Web App config and public VAPID key on the server.');
            const [appSdk, messageSdk] = await Promise.all([
                import('https://www.gstatic.com/firebasejs/12.19.0/firebase-app.js'),
                import('https://www.gstatic.com/firebasejs/12.19.0/firebase-messaging.js'),
            ]);
            if (!await messageSdk.isSupported()) throw Error('Web push is not supported in this browser. On iPhone/iPad, add the site to Home Screen and open it there.');
            sdk = messageSdk;
            const app = appSdk.getApps().find(a => a.name === 'growya-push') || appSdk.initializeApp(config.firebase, 'growya-push');
            messaging = sdk.getMessaging(app);
            registration = await navigator.serviceWorker.register(settings.workerUrl, {scope: '/', updateViaCache: 'none'});
            await registration.update();
            await Promise.race([navigator.serviceWorker.ready, new Promise((_, reject) => setTimeout(() => reject(Error('Notification worker did not start. Reload and try again.')), 15000))]);
            const token = await sdk.getToken(messaging, {vapidKey: config.vapid_key, serviceWorkerRegistration: registration});
            if (!token) throw Error('Could not register this browser. Check notification permission and Firebase settings.');
            await request(settings.registerUrl, {fcm_token: token});
            if (unsubscribe) unsubscribe();
            unsubscribe = sdk.onMessage(messaging, async payload => {
                const data = payload.data || {};
                document.dispatchEvent(new CustomEvent('growya:push', {detail: data}));
                let action; try { action = new URL(data.action_url || '/', location.origin); } catch (_) {}
                if (data.type === 'chat_message' && !document.hidden && action?.pathname === location.pathname) return;
                try {
                    await registration.showNotification(data.title || payload.notification?.title || 'Growya notification', {
                        body: data.body || payload.notification?.body || '', tag: 'growya-' + (data.recipient_id || 'alert'),
                        data: {url: data.url || '/my-notifications'},
                    });
                } catch (_) { say('A new alert arrived. Open Notifications to view it.', false, true); }
            });
            ready = true;
            say('Browser alerts enabled. Messages, orders and enquiries can arrive in the background.');
        })();
        try { return await busy; } finally { busy = null; }
    }
    async function enable() {
        if (!supported()) { say('Use HTTPS and a browser with notification support.', true, true); return; }
        try {
            // Ask directly from the user's click, before any network request.
            const permission = Notification.permission === 'granted' ? 'granted' : await Notification.requestPermission();
            if (permission !== 'granted') throw Error('Notifications are blocked. Allow them in this browser’s site settings, then try again.');
            try { localStorage.removeItem(optKey()); } catch (_) {}
            await connect(await request(settings.configUrl));
        } catch (error) { ready = false; say(friendly(error), true, true); }
    }
    function friendly(error) {
        if (error.name === 'AbortError') return 'Notification setup timed out. Check your connection and retry.';
        if (error.code?.startsWith('messaging/')) return 'Firebase registration failed. Check the Web App config, VAPID key and notification permission.';
        return error.message || 'Unable to enable alerts. Please try again.';
    }
    async function refresh() {
        const current = readSettings();
        if (!current) { ready = false; return; }
        if (current.userId !== settings.userId) { location.reload(); return; }
        settings = current;
        if (Date.now() - checkedAt < 60000) return;
        checkedAt = Date.now();
        try {
            const config = await request(settings.configUrl);
            renderDeliveries(config);
            if (!config.enabled) { ready = false; say('Browser push is awaiting Firebase Web App configuration. Dashboard alerts still work.'); return; }
            if (!supported()) { say('This browser needs HTTPS and web push support.', true); return; }
            if (optedOut()) { ready = false; say('Alerts are disabled for this browser. Click Enable browser alerts to turn them on.'); return; }
            if (Notification.permission === 'granted') await connect(config);
            else say(Notification.permission === 'denied' ? 'Notifications are blocked in this browser’s site settings.' : 'Click Enable browser alerts and allow notifications.');
        } catch (error) { say(friendly(error), true); }
    }
    async function disable() {
        try {
            if (busy) await busy.catch(() => {});
            await request(settings.disableUrl, {}); ready = false;
            try { localStorage.setItem(optKey(), 'yes'); } catch (_) {}
            if (unsubscribe) { unsubscribe(); unsubscribe = null; }
            if (sdk && messaging) await sdk.deleteToken(messaging);
            say('Notifications disabled for this browser.', false, true);
        } catch (error) { say(friendly(error), true, true); }
    }
    async function test() {
        try { say((await request(settings.testUrl, {})).message, false, true); document.dispatchEvent(new CustomEvent('growya:push')); }
        catch (error) { say(friendly(error), true, true); }
    }
    window.GrowyaPush = {enable, disable, test, refresh, active: () => ready && !optedOut()};
    document.addEventListener('click', event => {
        if (event.target.closest('[data-push-enable]')) enable();
        if (event.target.closest('[data-push-disable]')) disable();
        if (event.target.closest('[data-push-test]')) test();
    });
    document.addEventListener('livewire:navigated', () => { checkedAt = 0; refresh(); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    window.addEventListener('storage', event => { if (event.key === optKey() && event.newValue === 'yes') ready = false; });
    setInterval(() => { if (!document.hidden && document.querySelector('[data-push-deliveries]')) request(settings.configUrl).then(renderDeliveries).catch(() => {}); }, 15000);
    refresh();
})();
