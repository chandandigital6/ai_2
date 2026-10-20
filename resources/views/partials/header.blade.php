<header class="sticky top-0 z-50 border-b border-white/10 bg-[#050e09]/88 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('assets/logo.jpeg') }}" class="h-11 w-11 rounded-2xl object-cover ring-1 ring-white/15" alt="SPINCRASHAPK">
            <div class="min-w-0">
                <div class="truncate font-black tracking-wide">SPINCRASHAPK</div>
                <div class="hidden text-[11px] text-brand-300 sm:block">Android Games • APK Library</div>
            </div>
        </a>

        <nav class="hidden gap-6 text-sm text-white/65 md:flex">
            <a href="{{ route('home') }}#offers" class="hover:text-white">Offers</a>
            <a href="{{ route('games.index') }}" class="hover:text-white">All Games</a>
            <a href="{{ route('home') }}#activity" class="hover:text-white">Community</a>
            <a href="{{ route('home') }}#faq" class="hover:text-white">FAQ</a>
        </nav>

        <a href="https://t.me/spincrashapk" target="_blank" rel="noopener"
           class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-extrabold hover:bg-brand-400">
            ✈ Telegram Updates
        </a>
    </div>
</header>
