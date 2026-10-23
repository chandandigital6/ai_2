// Register the click handler BEFORE Firebase libraries so it cannot be replaced.
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil((async () => {
        const data = event.notification.data || {};
        let url;
        try { url = new URL(data.url || '/my-notifications', self.location.origin); }
        catch (_) { return; }
        if (url.origin !== self.location.origin) return;
        const tabs = await self.clients.matchAll({type: 'window', includeUncontrolled: true});
        for (const tab of tabs) {
            if (new URL(tab.url).origin !== self.location.origin) continue;
            try { const client = await tab.navigate(url.href); if (client) { await client.focus(); return; } } catch (_) {}
        }
        await self.clients.openWindow(url.href);
    })());
});
const firebaseConfig = @json($firebase);
if (firebaseConfig) {
    importScripts('https://www.gstatic.com/firebasejs/12.19.0/firebase-app-compat.js');
    importScripts('https://www.gstatic.com/firebasejs/12.19.0/firebase-messaging-compat.js');
    firebase.initializeApp(firebaseConfig);
    firebase.messaging().onBackgroundMessage(payload => {
        const data = payload.data || {};
        // Web messages are data-only: this creates exactly one background notification.
        return self.registration.showNotification(data.title || 'Growya notification', {
            body: data.body || 'You have a new alert',
            tag: 'growya-' + (data.recipient_id || data.delivery_id || 'alert'),
            icon: '/apple-touch-icon.png',
            data: {url: data.url || '/my-notifications'},
        });
    });
}
