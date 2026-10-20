<article class="game-card glass rounded-[1.7rem] p-5" data-game-card
         data-name="{{ strtolower($game['name']) }}"
         data-category="{{ strtolower($game['category']) }}"
         data-description="{{ strtolower($game['description']) }}"
         data-referral="{{ strtolower($game['referral_code']) }}"
         data-rating="{{ $game['rating'] }}">
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            @if(!empty($game['icon']))
                <img src="{{ asset($game['icon']) }}" alt="{{ $game['name'] }}" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-white/10">
            @else
                <div class="grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500/25 to-yellow-400/10 text-3xl ring-1 ring-white/10">
                    {{ $game['emoji'] ?? '🎮' }}
                </div>
            @endif

            <div class="min-w-0">
                <span class="text-[10px] font-black uppercase tracking-[.16em] text-brand-300">{{ $game['tag'] }}</span>
                <h3 class="mt-1 truncate text-lg font-black">{{ $game['name'] }}</h3>
                <p class="text-xs text-white/40">{{ $game['category'] }} • v{{ $game['version'] }}</p>
            </div>
        </div>

        <span class="rounded-lg bg-yellow-300/10 px-2 py-1 text-xs font-bold text-yellow-300">★ {{ $game['rating'] }}</span>
    </div>

    <p class="mt-4 line-clamp-2 min-h-[42px] text-sm leading-6 text-white/48">{{ $game['description'] }}</p>

    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
        <div class="rounded-xl bg-white/[.045] p-3">
            <span class="block text-white/35">Downloads*</span>
            <b class="mt-1 block text-sm">{{ $game['downloads'] }}</b>
        </div>
        <div class="rounded-xl bg-white/[.045] p-3">
            <span class="block text-white/35">Referral</span>
            <b class="mt-1 block truncate text-sm text-yellow-300">{{ $game['referral_code'] }}</b>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2">
        <a href="{{ asset($game['apk']) }}" download
           class="rounded-xl bg-brand-500 px-3 py-3 text-center text-sm font-extrabold hover:bg-brand-400">⬇ Download</a>
        <a href="{{ route('games.show', $game['slug']) }}"
           class="rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-center text-sm font-bold hover:bg-white/10">View Details</a>
    </div>
</article>
