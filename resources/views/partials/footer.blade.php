<footer class="border-t border-white/10 bg-black/10">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/logo.jpeg') }}" class="h-10 w-10 rounded-xl object-cover" alt="grova_ai">
                <b>grova_ai</b>
            </div>
            <p class="mt-3 text-sm leading-6 text-white/40"> grova_ai is a platform for discovering and downloading the latest Android games. </p>
        </div>

        <div>
            <h3 class="font-black">Quick Links</h3>
            <div class="mt-3 grid gap-2 text-sm text-white/45">
                <a href="{{ route('home') }}#offers" class="hover:text-white">Offers</a>
                <a href="{{ route('games.index') }}" class="hover:text-white">All Games</a>
                <a href="{{ route('home') }}#faq" class="hover:text-white">FAQ</a>
            </div>
        </div>

        <div>
            <h3 class="font-black">Customer Support</h3>
            <a href="https://t.me/spincrashapksupport" target="_blank" rel="noopener"
               class="mt-3 flex items-center gap-3 rounded-2xl border border-sky-400/15 bg-sky-400/[.07] p-3 transition hover:bg-sky-400/10">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-400/15 text-xl">✈</span>
                <span>
                    <b class="block text-sm text-sky-300">Telegram Support</b>
                    <small class="text-white/40">@spincrashapksupport</small>
                </span>
            </a>
        </div>

        <div>
            <h3 class="font-black">Daily Updates</h3>
            <a href="https://t.me/spincrashapk" target="_blank" rel="noopener"
               class="mt-3 flex items-center gap-3 rounded-2xl border border-brand-400/15 bg-brand-400/[.07] p-3 transition hover:bg-brand-400/10">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-400/15 text-xl">📢</span>
                <span>
                    <b class="block text-sm text-brand-300">Telegram Channel</b>
                    <small class="text-white/40">@spincrashapk</small>
                </span>
            </a>
        </div>
    </div>

    <div class="border-t border-white/10 py-5 text-center text-xs text-white/30">
        © {{ now()->year }} SPINCRASHAPK. All rights reserved.
    </div>
</footer>
