{{--
    Usage:
    @include('public.partials.wishlist-save', ['type' => 'product', 'itemId' => $product->id])
    @include('public.partials.wishlist-save', ['type' => 'service', 'itemId' => $service->id])
--}}
@php
    $wlUserId = auth()->id();
    $wlSaved = false;
    $wlConsent = false;

    if ($wlUserId) {
        $wlSaved = $type === 'product'
            ? \App\Models\ProductFavourite::where('user_id', $wlUserId)->where('product_id', $itemId)->exists()
            : \Illuminate\Support\Facades\DB::table('service_favourites')->where('user_id', $wlUserId)->where('service_id', $itemId)->exists();

        if ($wlSaved) {
            $wlConsent = (bool) \App\Models\WishlistInterest::where('user_id', $wlUserId)
                ->where('item_type', $type)
                ->where('item_id', $itemId)
                ->value('contact_consent');
        }
    }
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-5">
    @auth
        @if ($wlSaved)
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-emerald-700"><span aria-hidden="true">♥</span> Saved to your wishlist</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $wlConsent ? 'Business aapse contact kar sakta hai.' : 'Business aapse contact nahi karega.' }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('growya.wishlist.remove', [$type, $itemId]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm font-semibold text-red-600 underline">Remove</button>
                    </form>
                    <a class="text-sm font-semibold text-slate-500 underline hover:text-emerald-700"
                        href="{{ route('growya.wishlist.mine') }}">My Wishlist</a>
                </div>
            </div>

            @unless ($wlConsent)
                <form method="POST" action="{{ route('growya.wishlist.add', [$type, $itemId]) }}"
                    class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                    @csrf
                    <input type="hidden" name="contact_consent" value="1">
                    <span class="text-sm text-slate-600">Seller ko contact karne ki permission dena chahte ho?</span>
                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-bold hover:border-emerald-600 hover:text-emerald-700">
                        Allow contact
                    </button>
                </form>
            @endunless
        @else
            <form method="POST" action="{{ route('growya.wishlist.add', [$type, $itemId]) }}" class="space-y-4">
                @csrf

                <label class="flex items-start gap-3 text-sm text-slate-600">
                    <input type="checkbox" name="contact_consent" value="1"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                    <span>Is item ke baare mein business mujhse contact kar sakta hai.
                        <span class="block text-xs text-slate-400">Optional. Tick na karo to seller aapse contact nahi karega.</span>
                    </span>
                </label>

                <div class="flex flex-wrap items-center gap-4">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-700 px-5 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                        <span aria-hidden="true">♡</span> Save to Wishlist
                    </button>
                    <a class="text-sm font-semibold text-slate-500 underline hover:text-emerald-700"
                        href="{{ route('growya.wishlist.mine') }}">My Wishlist</a>
                </div>
            </form>
        @endif
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-600">Wishlist mein save karne ke liye login karo.</p>
            <a href="{{ route('login') }}"
                class="inline-flex rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-800 hover:border-emerald-600 hover:text-emerald-700">
                Log in
            </a>
        </div>
    @endauth
</div>