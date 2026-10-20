/* Progressive enhancement only: every form and link works without this file. */
(() => {
    if (window.__growyaThemeLoaded) return;
    window.__growyaThemeLoaded = true;
    let closeDrawer = () => {};
    function init() {
        closeDrawer();
        const sidebar = document.querySelector('[data-g-sidebar]');
        const overlay = document.querySelector('[data-g-overlay]');
        const toggle = document.querySelector('[data-g-menu-toggle]');
        let previousFocus;
        function setDrawer(open) {
            if (!sidebar || !overlay || !toggle) return;
            sidebar.classList.toggle('is-open', open);
            overlay.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            document.body.style.overflow = open ? 'hidden' : '';
            if (open) {
                previousFocus = document.activeElement;
                sidebar.querySelector('[data-g-menu-close]')?.focus();
            } else if (previousFocus?.isConnected && !sidebar.contains(document.activeElement)) {
                previousFocus.focus();
            }
            if (window.innerWidth < 1024) {
                sidebar.inert = !open;
                sidebar.setAttribute('aria-hidden', String(!open));
            } else {
                sidebar.inert = false;
                sidebar.removeAttribute('aria-hidden');
            }
        }
        closeDrawer = () => setDrawer(false);
        if (sidebar && sidebar.dataset.ready !== '1') {
            sidebar.dataset.ready = '1';
            toggle?.addEventListener('click', () => setDrawer(true));
            sidebar.querySelector('[data-g-menu-close]')?.addEventListener('click', () => {
                setDrawer(false); toggle?.focus();
            });
            overlay?.addEventListener('click', () => { setDrawer(false); toggle?.focus(); });
            sidebar.addEventListener('click', e => { if (e.target.closest('a')) setDrawer(false); });
            sidebar.addEventListener('keydown', e => {
                if (e.key !== 'Tab' || !sidebar.classList.contains('is-open')) return;
                const targets = [...sidebar.querySelectorAll('a,button,input,select,[tabindex="0"]')].filter(el => el.getClientRects().length && !el.disabled);
                const first = targets[0], last = targets.at(-1);
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last?.focus(); }
                if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first?.focus(); }
            });
            setDrawer(false);
        }
        document.querySelectorAll('[data-g-password-toggle]').forEach(button => {
            if (button.dataset.ready) return;
            button.dataset.ready = '1';
            button.addEventListener('click', () => {
                const input = document.getElementById(button.getAttribute('aria-controls'));
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.textContent = show ? 'Hide' : 'Show';
                button.setAttribute('aria-pressed', String(show));
            });
        });
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            const wasOpen = document.querySelector('[data-g-sidebar].is-open');
            closeDrawer();
            if (wasOpen) document.querySelector('[data-g-menu-toggle]')?.focus();
            document.querySelectorAll('.g-public-menu[open]').forEach(el => el.open = false);
        }
    });
    window.addEventListener('resize', () => closeDrawer());
    document.addEventListener('click', e => {
        document.querySelectorAll('.g-public-menu[open]').forEach(el => {
            if (!el.contains(e.target) || e.target.closest('a')) el.open = false;
        });
    });
    document.addEventListener('livewire:navigating', () => closeDrawer());
    document.addEventListener('livewire:navigated', init);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
