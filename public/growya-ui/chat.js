(function () {
    function init() {
        const root = document.getElementById('chat-messages');
        if (!root || root.dataset.initialized) return;
        root.dataset.initialized = 'yes'; root.scrollTop = root.scrollHeight;
        const status = document.getElementById('chat-live-status');
        const file = document.getElementById('chat-file'), label = document.getElementById('chat-file-name');
        file?.addEventListener('change', () => { label.textContent = file.files[0]?.name || 'JPG, PNG, WEBP ya PDF · max 5 MB'; });
        const form = document.getElementById('chat-form');
        document.getElementById('chat-body')?.addEventListener('keydown', event => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { event.preventDefault(); form?.requestSubmit(); }
        });
        if (root.dataset.live !== '1') return;
        let last = Number(root.dataset.lastId) || 0, busy = false, nextPollAt = 0, failures = 0, stopped = false;
        async function poll() {
            if (!root.isConnected || document.hidden || busy || stopped || Date.now() < nextPollAt) return;
            busy = true; nextPollAt = Date.now() + 3000;
            const controller = new AbortController(), timeout = setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(root.dataset.pollUrl + '?after=' + last, {credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: {Accept: 'application/json'}});
                if (response.status === 429) {
                    const retry = response.headers.get('Retry-After');
                    const wait = /^\d+$/.test(retry || '') ? Number(retry) * 1000 : Math.max(0, Date.parse(retry) - Date.now()) || 60000;
                    nextPollAt = Date.now() + Math.max(1000, wait);
                    status.textContent = 'Messages will refresh in ' + Math.ceil(wait / 1000) + ' seconds.';
                    return;
                }
                if ([401, 403, 404, 419].includes(response.status)) stopped = true;
                if (!response.ok) throw Error(response.status === 401 || response.status === 403 ? 'Session or chat access changed. Reload the page.' : 'Connection interrupted. Retrying automatically…');
                const json = await response.json(), messages = json.messages || [];
                if (!root.isConnected) return;
                const nearBottom = root.scrollHeight - root.clientHeight - root.scrollTop < 120;
                for (const message of messages) {
                    if (Number(message.id) <= last) continue;
                    last = Number(message.id);
                    root.querySelector('[data-empty-chat]')?.remove();
                    const row = document.createElement('div'); row.className = 'flex ' + (message.mine ? 'justify-end' : 'justify-start');
                    const bubble = document.createElement('div'); bubble.className = 'max-w-[85%] rounded-2xl px-4 py-3 ' + (message.mine ? 'rounded-br-sm bg-emerald-700 text-white' : 'rounded-bl-sm bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white');
                    if (message.body) { const body = document.createElement('p'); body.className = 'whitespace-pre-wrap break-words text-sm'; body.textContent = message.body; bubble.append(body); }
                    if (message.attachment) {
                        const link = document.createElement('a'); link.href = message.attachment; link.textContent = '📎 Attachment'; link.className = 'mt-1 block text-xs font-semibold underline'; bubble.append(link);
                    }
                    const time = document.createElement('small'); time.className = 'mt-1 block text-[11px] opacity-70'; time.textContent = message.time; bubble.append(time);
                    row.append(bubble); root.append(row);
                }
                if (nearBottom && messages.length) root.scrollTop = root.scrollHeight;
                failures = 0;
                status.textContent = 'Live messages connected';
            } catch (error) { nextPollAt = Date.now() + Math.min(60000, 8000 * 2 ** Math.min(++failures, 3)); if (root.isConnected) status.textContent = error.message || 'Unable to refresh messages. Retrying automatically…'; }
            finally { clearTimeout(timeout); busy = false; }
        }
        const onVisible = () => { if (!document.hidden) poll(); };
        const onPush = event => { if (String(event.detail?.thread_id) === root.dataset.threadId) poll(); };
        document.addEventListener('visibilitychange', onVisible); document.addEventListener('growya:push', onPush);
        const timer = setInterval(() => {
            if (!root.isConnected) { clearInterval(timer); document.removeEventListener('visibilitychange', onVisible); document.removeEventListener('growya:push', onPush); return; }
            poll();
        }, 8000);
        poll();
    }
    if (!window.growyaChatInit) { window.growyaChatInit = init; document.addEventListener('livewire:navigated', init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
