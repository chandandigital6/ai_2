@auth
    @php
        $alreadyFollowing = \App\Models\BusinessFollower::query()
            ->where('business_id', $business->id)
            ->where('user_id', auth()->id())
            ->exists();
    @endphp

    <form method="POST"
        action="{{ $alreadyFollowing
            ? route('public.business.unfollow', $business)
            : route('public.business.follow', $business) }}">
        @csrf

        @if ($alreadyFollowing)
            @method('DELETE')
        @endif

        <button type="submit"
            class="group inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold transition
            {{ $alreadyFollowing
                ? 'border-emerald-600 bg-emerald-600 text-white hover:border-rose-500 hover:bg-rose-500'
                : 'border-emerald-600 bg-white text-emerald-700 hover:bg-emerald-50' }}">
            @if ($alreadyFollowing)
                <span class="group-hover:hidden">✓ Following</span>
                <span class="hidden group-hover:inline">Unfollow</span>
            @else
                <span>+ Follow</span>
            @endif
        </button>
    </form>
@else
    <a href="{{ route('login') }}"
        class="inline-flex items-center gap-2 rounded-xl border border-emerald-600 bg-white px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50">
        + Follow
    </a>
@endauth