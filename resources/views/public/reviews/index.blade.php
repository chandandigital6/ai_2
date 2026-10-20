@extends('public.businesses.layout')

@section('title', 'Reviews | ' . $business->name)

@section('content')

    @php
        $total = (int) ($summary->total ?? 0);
        $average = $total ? (float) $summary->average_rating : 0;

        // Star-wise breakdown (approved reviews)
        $breakdown = \App\Models\Review::query()
            ->where('business_id', $business->id)
            ->where('status', 'approved')
            ->selectRaw('rating, COUNT(*) as c')
            ->groupBy('rating')
            ->pluck('c', 'rating');

        $stars = fn(float $n) => str_repeat('★', (int) round($n)) . str_repeat('☆', 5 - (int) round($n));

        $isOwner = auth()->check() && (int) auth()->id() === (int) $business->owner_id;
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black md:text-3xl">Reviews</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $business->name }}</p>
            </div>

            <a href="{{ route('public.business.home', $business->slug) }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-slate-50">
                ← Business Showroom
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Rating summary --}}
        <section class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-6 md:grid-cols-[220px_1fr]">
            <div class="text-center md:border-r md:border-slate-100 md:pr-6">
                @if ($total)
                    <p class="text-5xl font-black text-slate-900">{{ number_format($average, 1) }}</p>
                    <p class="mt-1 text-xl tracking-wide text-amber-500">{{ $stars($average) }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $total }} {{ $total === 1 ? 'review' : 'reviews' }}</p>
                @else
                    <p class="text-4xl font-black text-slate-300">—</p>
                    <p class="mt-2 text-sm text-slate-500">Not rated yet</p>
                @endif
            </div>

            <div class="space-y-2">
                @foreach ([5, 4, 3, 2, 1] as $star)
                    @php
                        $count = (int) ($breakdown[$star] ?? 0);
                        $percent = $total ? round(($count / $total) * 100) : 0;
                    @endphp

                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-10 shrink-0 font-semibold text-slate-600">{{ $star }} ★</span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-amber-400" style="width: {{ $percent }}%"></div>
                        </div>
                        <span class="w-8 shrink-0 text-right text-slate-500">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Review form --}}
        @auth
            @if (!$isOwner)
                <form method="POST"
                    action="{{ $myReview
                        ? route('public.business.reviews.update', [$business->slug, $myReview])
                        : route('public.business.reviews.store', $business->slug) }}"
                    class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6">
                    @csrf

                    @if ($myReview)
                        @method('PUT')
                    @endif

                    <div>
                        <h2 class="text-lg font-black">{{ $myReview ? 'Edit Your Review' : 'Write a Review' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Share your experience with {{ $business->name }}.</p>
                    </div>

                    @if ($myReview)
                        @php
                            $statusClass = match ($myReview->status) {
                                'approved' => 'bg-emerald-50 text-emerald-700',
                                'rejected' => 'bg-rose-50 text-rose-700',
                                default => 'bg-amber-50 text-amber-700',
                            };
                        @endphp

                        <p class="rounded-xl px-4 py-3 text-sm font-semibold {{ $statusClass }}">
                            Your review status: {{ ucfirst($myReview->status) }}. Editing sends it back for approval.
                        </p>
                    @endif

                    {{-- Star rating (CSS only) --}}
                    @php $currentRating = (int) old('rating', $myReview?->rating ?? 5); @endphp

                    <div>
                        <p class="mb-2 text-sm font-bold">Your Rating</p>

                        <div class="flex flex-row-reverse justify-end gap-1">
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" name="rating" id="star-{{ $i }}" value="{{ $i }}"
                                    class="peer sr-only" @checked($currentRating === $i) required>
                                <label for="star-{{ $i }}" title="{{ $i }} star{{ $i > 1 ? 's' : '' }}"
                                    class="cursor-pointer text-4xl leading-none text-slate-300 transition hover:text-amber-400 peer-checked:text-amber-400 peer-hover:text-amber-400">★</label>
                            @endfor
                        </div>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold">Your Experience</span>
                        <textarea name="review" required minlength="5" maxlength="3000" rows="4"
                            placeholder="What did you like or dislike?"
                            class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm outline-emerald-500">{{ old('review', $myReview?->review) }}</textarea>
                    </label>

                    <button class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                        {{ $myReview ? 'Update Review' : 'Submit Review' }}
                    </button>
                </form>
            @else
                <p class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">
                    You are the owner of this business, so you cannot review it.
                </p>
            @endif
        @else
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-600">Want to leave a review? Login first.</p>
                <a href="{{ route('login') }}" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white">Log
                    in</a>
            </div>
        @endauth

        @if(auth()->check() && $myReview)
        <form action="{{ route('extensions.review-photos.store', $myReview) }}" method="POST" enctype="multipart/form-data" class="g-panel p-5 space-y-3">@csrf
            <h2 class="font-bold">Your review photos</h2><p>Up to 5 photos, JPG/PNG/WEBP, 3 MB each. New photos go through moderation.</p>
            <input name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required><button class="g-btn g-btn-primary">Upload photos</button>
        </form>
        @endif
        {{-- Reviews list --}}
        <section class="space-y-4">
            <h2 class="text-lg font-black">Customer Reviews</h2>

            @forelse($reviews as $review)
                @php $name = $review->user?->name ?? 'Customer'; @endphp

                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-start gap-3">
                        <div
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-100 font-black text-emerald-800">
                            {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong class="truncate">{{ $name }}</strong>
                                <span class="text-xs text-slate-400">{{ $review->created_at?->format('d M Y') }}</span>
                            </div>

                            <p class="mt-0.5 text-amber-500">
                                {{ str_repeat('★', (int) $review->rating) }}<span
                                    class="text-slate-300">{{ str_repeat('★', 5 - (int) $review->rating) }}</span>
                            </p>

                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $review->review }}</p>

                            <div class="flex flex-wrap gap-3 mt-3">@foreach($review->photos as $photo)<a href="{{ $photo['url'] }}" target="_blank" rel="noopener"><img src="{{ $photo['url'] }}" alt="Customer review photo" loading="lazy" class="h-24 w-24 rounded-xl object-cover"></a>@endforeach</div>
                            @if ($review->business_reply)
                                <div class="mt-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-4">
                                    <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">
                                        Response from {{ $business->name }}
                                    </p>
                                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                                        {{ $review->business_reply }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <p class="font-bold text-slate-700">No approved reviews yet</p>
                    <p class="mt-1 text-sm text-slate-500">Be the first to share your experience.</p>
                </div>
            @endforelse
        </section>

        <div>{{ $reviews->links() }}</div>
    </div>

@endsection
