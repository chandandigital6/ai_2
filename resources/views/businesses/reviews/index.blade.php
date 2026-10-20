<x-layouts::app title="Business Reviews">

    @php
        $canReply = app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'reviews.manage');

        $badge = fn(string $status) => match ($status) {
            'approved' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            default => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        };
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Reviews</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ $business->name }}</p>
            </div>

            <a href="{{ route('businesses.index') }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                ← Back to Businesses
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Reviews --}}
        @forelse($reviews as $review)
            @php $name = $review->user?->name ?? 'Customer'; @endphp

            <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-800">
                            {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                        </div>

                        <div>
                            <p class="font-semibold">{{ $name }}</p>
                            <p class="text-sm text-amber-500">
                                {{ str_repeat('★', (int) $review->rating) }}<span
                                    class="text-zinc-300 dark:text-zinc-600">{{ str_repeat('★', 5 - (int) $review->rating) }}</span>
                                <span class="ml-1 text-xs text-zinc-500">{{ $review->rating }}/5</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <span class="rounded-full px-3 py-1 font-semibold {{ $badge($review->status) }}">
                            {{ ucfirst($review->status) }}
                        </span>
                        <span class="text-zinc-500">{{ $review->created_at->format('d M Y') }}</span>
                    </div>
                </div>

                <p class="whitespace-pre-line text-sm leading-6">{{ $review->review }}</p>

                @if ($review->business_reply)
                    <div class="rounded-xl border-l-4 border-emerald-500 bg-zinc-100 p-4 dark:bg-zinc-800">
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                            Business reply
                            @if ($review->replied_at)
                                <span class="font-normal normal-case text-zinc-500">
                                    · {{ \Illuminate\Support\Carbon::parse($review->replied_at)->format('d M Y') }}
                                </span>
                            @endif
                        </p>
                        <p class="mt-1 whitespace-pre-line text-sm">{{ $review->business_reply }}</p>
                    </div>
                @endif

                @if ($canReply && $review->status === 'approved')
                    <form method="POST" action="{{ route('businesses.reviews.reply', [$business, $review]) }}"
                        class="space-y-2">
                        @csrf
                        @method('PATCH')

                        <textarea name="business_reply" required rows="2" maxlength="2000"
                            class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            placeholder="Reply to this review...">{{ old('business_reply', $review->business_reply) }}</textarea>

                        <button
                            class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                            {{ $review->business_reply ? 'Update Reply' : 'Save Reply' }}
                        </button>
                    </form>
                @elseif ($canReply && $review->status !== 'approved')
                    <p class="text-xs text-zinc-500">Reply tabhi de sakte ho jab review approved ho.</p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <p class="font-semibold">No reviews yet</p>
                <p class="mt-1 text-sm text-zinc-500">No reviews submitted for this business.</p>
            </div>
        @endforelse

        <div>{{ $reviews->links() }}</div>
    </div>

</x-layouts::app>
