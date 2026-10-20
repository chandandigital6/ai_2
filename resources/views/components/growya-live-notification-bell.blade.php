@auth
@php
    $growyaBellId = 'growya-live-bell-' . auth()->id();
    $growyaInitialCount = \App\Models\NotificationRecipient::query()
        ->where('user_id', auth()->id())
        ->whereNull('opened_at')
        ->whereHas('notification', fn ($q) => $q->where('status', 'sent'))
        ->count();
@endphp
<div id="{{ $growyaBellId }}"
     data-poll-url="{{ route('growya.notifications.poll') }}"
     data-read-all-url="{{ route('growya.notifications.read-all') }}"
     data-notifications-url="{{ route('growya.notifications.index') }}"
     data-user-id="{{ auth()->id() }}"
     class="relative inline-block text-left">
    <button type="button" data-bell-toggle aria-expanded="false" aria-haspopup="true"
            class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <span aria-hidden="true">🔔</span><span>Notifications</span>
        <span data-bell-count class="{{ $growyaInitialCount ? '' : 'hidden' }} rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ $growyaInitialCount > 99 ? '99+' : $growyaInitialCount }}</span>
    </button>
    <section data-bell-menu hidden class="absolute right-0 z-50 mt-2 w-[min(92vw,390px)] overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-2 border-b border-zinc-200 p-3 dark:border-zinc-700">
            <strong class="text-sm">Recent unread alerts</strong>
            <a href="{{ route('growya.notifications.index') }}" class="text-xs font-semibold text-emerald-700">View all</a>
        </div>
        <div data-bell-items class="max-h-80 overflow-y-auto p-2" aria-live="polite">
            <p class="p-4 text-sm text-zinc-500">Loading notifications…</p>
        </div>
        <div class="flex flex-wrap gap-2 border-t border-zinc-200 p-3 text-xs dark:border-zinc-700">
            <button type="button" data-browser-enable class="rounded-lg border px-3 py-2 dark:border-zinc-700">Enable desktop alerts</button>
            <button type="button" data-sound-toggle class="rounded-lg border px-3 py-2 dark:border-zinc-700">Sound: Off</button>
            <form method="POST" action="{{ route('growya.notifications.read-all') }}" class="ml-auto">
                @csrf @method('PATCH')
                <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-2 text-white">Mark all read</button>
            </form>
        </div>
        <p data-bell-notice class="px-3 pb-2 text-xs text-zinc-500" role="status"></p>
    </section>
</div>
@once
<script>
(function () {
    function initGrowyaBells() {
        document.querySelectorAll('[id^="growya-live-bell-"]').forEach(function (root) {
            if (root.dataset.liveInitialized === '1') return;
            root.dataset.liveInitialized = '1';
            const toggle = root.querySelector('[data-bell-toggle]');
            const menu = root.querySelector('[data-bell-menu]');
            const countLabel = root.querySelector('[data-bell-count]');
            const list = root.querySelector('[data-bell-items]');
            const notice = root.querySelector('[data-bell-notice]');
            const desktopBtn = root.querySelector('[data-browser-enable]');
            const soundBtn = root.querySelector('[data-sound-toggle]');
            const storeKey = 'growya-bell-sound-' + root.dataset.userId;
            let soundEnabled = false;
            try { soundEnabled = localStorage.getItem(storeKey) === 'yes'; } catch (_) {}
            let lastSeen = null;
            let running = false;
            let audioContext = null;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value || '';

            function textNode(tag, text, className) {
                const node = document.createElement(tag);
                node.textContent = text == null ? '' : String(text);
                if (className) node.className = className;
                return node;
            }
            function render(items) {
                list.replaceChildren();
                if (!items.length) {
                    list.appendChild(textNode('p', 'You are all caught up!', 'p-4 text-sm text-zinc-500'));
                    return;
                }
                items.forEach(function (item) {
                    // A POST form is required by the existing notification/open endpoint.
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = item.open_url;
                    form.className = 'border-b border-zinc-100 p-2 last:border-0 dark:border-zinc-800';
                    const token = document.createElement('input');
                    token.type = 'hidden'; token.name = '_token'; token.value = csrf;
                    form.appendChild(token);
                    const button = document.createElement('button');
                    button.type = 'submit'; button.className = 'w-full rounded-lg p-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800';
                    button.appendChild(textNode('span', item.title, 'block text-sm font-semibold'));
                    button.appendChild(textNode('span', item.message, 'mt-1 block text-xs text-zinc-600 dark:text-zinc-300'));
                    button.appendChild(textNode('span', item.created_at, 'mt-1 block text-xs text-zinc-400'));
                    form.appendChild(button); list.appendChild(form);
                });
            }
            function playSound() {
                if (!soundEnabled) return;
                try {
                    audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
                    if (audioContext.state === 'suspended') audioContext.resume();
                    const osc = audioContext.createOscillator();
                    const gain = audioContext.createGain();
                    osc.frequency.value = 740; osc.type = 'sine';
                    gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.07, audioContext.currentTime + 0.03);
                    gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.18);
                    osc.connect(gain); gain.connect(audioContext.destination);
                    osc.start(); osc.stop(audioContext.currentTime + 0.19);
                } catch (_) {}
            }
            function desktopAlert(item) {
                if (!('Notification' in window) || Notification.permission !== 'granted') return;
                try {
                    const n = new Notification(item.title || 'Growya notification', {
                        body: item.message || 'You have a new alert', tag: 'growya-' + item.id,
                    });
                    n.onclick = function () { window.focus(); n.close(); menu.hidden = false; };
                } catch (_) {}
            }
            async function poll() {
                if (!root.isConnected || running || document.visibilityState === 'hidden') return;
                running = true;
                try {
                    const res = await fetch(root.dataset.pollUrl, {
                        credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store',
                    });
                    if (!res.ok) throw Error('Unable to load');
                    const data = await res.json();
                    const count = Number(data.count) || 0;
                    countLabel.textContent = count > 99 ? '99+' : String(count);
                    countLabel.classList.toggle('hidden', count === 0);
                    render(Array.isArray(data.items) ? data.items : []);
                    const newest = Number(data.latest_id) || 0;
                    if (lastSeen !== null && newest > lastSeen && data.items?.length) {
                        const newOnes = data.items.filter(i => Number(i.id) > lastSeen);
                        if (newOnes.length) { playSound(); desktopAlert(newOnes[0]); }
                    }
                    lastSeen = newest;
                    notice.textContent = '';
                } catch (e) {
                    notice.textContent = 'Connection unavailable; will retry automatically.';
                } finally { running = false; }
            }
            toggle.addEventListener('click', function () {
                menu.hidden = !menu.hidden;
                toggle.setAttribute('aria-expanded', String(!menu.hidden));
                if (!menu.hidden) poll();
            });
            document.addEventListener('click', function (e) {
                if (!root.contains(e.target)) {
                    menu.hidden = true; toggle.setAttribute('aria-expanded', 'false');
                }
            });
            desktopBtn.addEventListener('click', async function () {
                if (!('Notification' in window)) { notice.textContent = 'Desktop alerts are not supported in this browser.'; return; }
                if (!window.isSecureContext) { notice.textContent = 'Desktop alerts need HTTPS (localhost is allowed).'; return; }
                try {
                    const status = await Notification.requestPermission();
                    notice.textContent = status === 'granted' ? 'Desktop alerts enabled while this page is open.' : 'Permission not granted. Check browser site settings.';
                } catch (_) { notice.textContent = 'Browser permission request failed.'; }
            });
            soundBtn.textContent = soundEnabled ? 'Sound: On' : 'Sound: Off';
            soundBtn.addEventListener('click', function () {
                soundEnabled = !soundEnabled;
                try { localStorage.setItem(storeKey, soundEnabled ? 'yes' : 'no'); } catch (_) {}
                soundBtn.textContent = soundEnabled ? 'Sound: On' : 'Sound: Off';
                if (soundEnabled) playSound(); // prime AudioContext on a real user click
            });
            document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
            poll();
            const timer = setInterval(function () {
                if (!root.isConnected) { clearInterval(timer); return; }
                poll();
            }, 20000);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initGrowyaBells);
    else initGrowyaBells();
    document.addEventListener('livewire:navigated', initGrowyaBells);
})();
</script>
@endonce
@endauth
