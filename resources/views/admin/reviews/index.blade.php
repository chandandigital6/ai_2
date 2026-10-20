<x-layouts::app title="Review Moderation">

    @php
        $tabs = [
            'pending' => ['label' => 'Pending', 'active' => 'border-amber-500 bg-amber-500 text-white', 'dot' => 'bg-amber-500'],
            'approved' => ['label' => 'Approved', 'active' => 'border-green-600 bg-green-600 text-white', 'dot' => 'bg-green-600'],
            'rejected' => ['label' => 'Rejected', 'active' => 'border-red-600 bg-red-600 text-white', 'dot' => 'bg-red-600'],
            'all' => ['label' => 'All', 'active' => 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900', 'dot' => 'bg-zinc-500'],
        ];

        $badge = fn(string $s) => match ($s) {
            'approved' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            default => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        };
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">

        <div>
            <h1 class="text-2xl font-bold">Business Review Moderation</h1>
            <p class="mt-1 text-sm text-zinc-500">Customer reviews ko approve ya reject karo.</p>
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

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($tabs as $key => $tab)
                <a href="{{ route('admin.business-reviews.index', ['status' => $key]) }}"
                    class="rounded-2xl border bg-white p-4 transition hover:shadow-sm dark:bg-zinc-900
                    {{ $status === $key ? 'border-zinc-900 dark:border-white' : 'border-zinc-200 dark:border-zinc-700' }}">
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <span class="h-2.5 w-2.5 rounded-full {{ $tab['dot'] }}"></span>
                        {{ $tab['label'] }}
                    </div>
                    <p class="mt-2 text-3xl font-black tabular-nums">{{ $counts[$key] }}</p>
                </a>
            @endforeach
        </div>

        {{-- Filter tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $key => $tab)
                <a href="{{ route('admin.business-reviews.index', ['status' => $key]) }}"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition
                    {{ $status === $key ? $tab['active'] : 'border-zinc-300 bg-white hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800' }}">
                    {{ $tab['label'] }}
                    <span
                        class="rounded-full px-2 py-0.5 text-xs {{ $status === $key ? 'bg-white/25' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                        {{ $counts[$key] }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Reviews --}}
        <div class="space-y-4">
            @forelse($reviews as $review)
                @php $name = $review->user?->name ?? 'Customer'; @endphp

                <article
                    class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

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
                            <span class="text-zinc-500">{{ $review->created_at?->format('d M Y') }}</span>
                        </div>
                    </div>

                    <p class="text-sm text-zinc-500">
                        Business:
                        @if ($review->business?->slug)
                            <a href="{{ route('public.business.home', $review->business->slug) }}" target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                                {{ $review->business->name }} ↗
                            </a>
                        @else
                            <span class="font-semibold">{{ $review->business?->name ?? 'Business' }}</span>
                        @endif
                    </p>

                    <p class="whitespace-pre-line rounded-xl bg-zinc-50 p-4 text-sm leading-6 dark:bg-zinc-800">{{ $review->review }}</p>

                    {{-- Moderation actions --}}
                    <div class="flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">

                        @if ($review->status !== 'approved')
                            <form method="POST" action="{{ route('admin.business-reviews.update', $review) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button
                                    class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                    ✓ Approve
                                </button>
                            </form>
                        @endif

                        @if ($review->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.business-reviews.update', $review) }}"
                                onsubmit="return confirm('Is review ko reject karna hai?')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button
                                    class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950">
                                    ✕ Reject
                                </button>
                            </form>
                        @endif

                        @if ($review->status !== 'pending')
                            <form method="POST" action="{{ route('admin.business-reviews.update', $review) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="pending">
                                <button
                                    class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                    ↺ Move to Pending
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                    <p class="font-semibold">No reviews here</p>
                    <p class="mt-1 text-sm text-zinc-500">Is filter mein koi review nahi hai.</p>
                </div>
            @endforelse
        </div>

        <div>{{ $reviews->links() }}</div>
    </div>

</x-layouts::app>