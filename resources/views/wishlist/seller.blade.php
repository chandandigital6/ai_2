@php
    $statuses = ['new', 'contacted', 'follow_up', 'converted', 'closed'];
    $statusLabel = fn ($s) => ucfirst(str_replace('_', ' ', $s));
    $badge = [
        'new' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'contacted' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        'follow_up' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'converted' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'closed' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    ];
@endphp

<x-layouts::app title="Wishlist Leads & Follow-ups">
<div id="main-content" class="mx-auto max-w-7xl space-y-5 p-6">
    <div>
        <h1 class="text-3xl font-bold">Wishlist Interests &amp; Follow-ups</h1>
        <p class="text-sm text-zinc-500">
            {{ $business?->name ?? 'Platform-wide wishlist leads — Super Admin' }}.
            Contact information is visible only when the customer has opted in.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-emerald-50 p-3 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($business)
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="rounded-lg border p-2 dark:border-zinc-700 dark:bg-zinc-900" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $statusLabel($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Filter</button>
            @if (request()->filled('status'))
                <a href="{{ route('businesses.wishlist.index', $business) }}" class="text-sm underline">Reset</a>
            @endif
        </form>
    @endif

    <div class="grid gap-4">
        @forelse ($rows as $interest)
            @php
                $itemName = $interest->item_type === 'product'
                    ? ($products[$interest->item_id] ?? 'Product removed')
                    : ($services[$interest->item_id] ?? 'Service removed');

                $action = $business
                    ? route('businesses.wishlist.update', [$business, $interest])
                    : route('growya.admin.wishlist.update', $interest);

                $isOverdue = $interest->follow_up_at
                    && $interest->follow_up_at->isPast()
                    && !in_array($interest->status, ['converted', 'closed'], true);

                // Validation errors sirf us card par dikhenge jo submit hua tha
                $isThisForm = (string) old('interest_id') === (string) $interest->id;
            @endphp

            <section class="space-y-3 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <strong>{{ $itemName }}</strong>
                        <div class="text-sm text-zinc-500">
                            {{ ucfirst($interest->item_type) }}
                            · {{ $interest->business?->name ?? $business?->name }}
                            · Saved {{ $interest->created_at?->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-medium">
                        @if ($isOverdue)
                            <span class="rounded-full bg-red-50 px-2 py-1 text-red-700 dark:bg-red-950 dark:text-red-300">Follow-up overdue</span>
                        @endif
                        <span class="rounded-full px-2 py-1 {{ $badge[$interest->status] ?? $badge['closed'] }}">
                            {{ $statusLabel($interest->status) }}
                        </span>
                    </div>
                </div>

                <p>Customer: <strong>{{ $interest->user?->name ?? 'Deleted user' }}</strong></p>

                @if ($interest->contact_consent && $interest->user)
                    <p class="text-sm">
                        Contact permission given ·
                        <a class="underline" href="mailto:{{ e($interest->user->email) }}">{{ $interest->user->email }}</a>
                        @if ($interest->user->phone)
                            · <a class="underline" href="tel:{{ e($interest->user->phone) }}">{{ $interest->user->phone }}</a>
                        @endif
                    </p>
                @else
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        No customer contact permission. Do not call/message based solely on this wishlist save.
                    </p>
                @endif

                <form method="POST" action="{{ $action }}" class="grid gap-3 md:grid-cols-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="interest_id" value="{{ $interest->id }}">

                    <label class="text-sm">Status
                        <select name="status" class="mt-1 w-full rounded-lg border p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected(($isThisForm ? old('status') : $interest->status) === $s)>{{ $statusLabel($s) }}</option>
                            @endforeach
                        </select>
                        @if ($isThisForm) @error('status') <span class="text-xs text-red-600">{{ $message }}</span> @enderror @endif
                    </label>

                    <label class="text-sm">Next follow-up
                        <input type="datetime-local" name="follow_up_at"
                               value="{{ $isThisForm ? old('follow_up_at') : $interest->follow_up_at?->format('Y-m-d\TH:i') }}"
                               class="mt-1 w-full rounded-lg border p-2 dark:border-zinc-700 dark:bg-zinc-900">
                        @if ($isThisForm) @error('follow_up_at') <span class="text-xs text-red-600">{{ $message }}</span> @enderror @endif
                    </label>

                    <label class="text-sm md:col-span-2">Internal note
                        <textarea name="seller_note" rows="2" maxlength="3000"
                                  class="mt-1 w-full rounded-lg border p-2 dark:border-zinc-700 dark:bg-zinc-900">{{ $isThisForm ? old('seller_note') : $interest->seller_note }}</textarea>
                        @if ($isThisForm) @error('seller_note') <span class="text-xs text-red-600">{{ $message }}</span> @enderror @endif
                    </label>

                    <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 font-semibold text-white md:col-span-4 md:justify-self-start">
                        Save Follow-up
                    </button>
                </form>

                @if ($interest->updater)
                    <p class="text-xs text-zinc-500">
                        Last updated by {{ $interest->updater->name }} · {{ $interest->updated_at?->format('d M Y H:i') }}
                    </p>
                @endif
            </section>
        @empty
            <p class="rounded-xl border p-8 text-center text-zinc-500 dark:border-zinc-700">No wishlist interests yet.</p>
        @endforelse
    </div>

    {{ $rows->links() }}
</div>
</x-layouts::app>