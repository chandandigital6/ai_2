<x-layouts::app :title="'CRM Customer'">
    @php
        $overdue =
            $customer->next_follow_up_at &&
            $customer->next_follow_up_at->isPast() &&
            !in_array($customer->status, ['converted', 'closed'], true);
    @endphp
    <div class="mx-auto max-w-4xl space-y-5 px-5 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">{{ $customer->name }}</h1>
                <p class="text-zinc-500">{{ $business->name }} / {{ ucfirst($customer->status) }}</p>
            </div>
            <a class="rounded-lg bg-emerald-700 px-4 py-2 text-white"
                href="{{ route('growya.crm.edit', [$business, $customer->id]) }}">Edit Customer</a>
        </div>

        @if (session('success'))
            <p class="rounded-lg bg-emerald-100 p-3 text-emerald-900">{{ session('success') }}</p>
        @endif
        {{-- Validation errors from the Add Activity form were never shown before --}}
        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-800">
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-3 rounded-xl border p-5 dark:border-zinc-700 sm:grid-cols-2">
            <div><strong>Email</strong>
                <p>{{ $customer->email ?? '—' }}</p>
            </div>
            <div><strong>Phone</strong>
                <p>{{ $customer->phone ?? '—' }}</p>
            </div>
            <div><strong>Company</strong>
                <p>{{ $customer->company ?? '—' }}</p>
            </div>
            <div><strong>Source</strong>
                <p>{{ ucfirst(str_replace('_', ' ', $customer->source)) }}</p>
            </div>
            <div><strong>Tags</strong>
                <p>{{ $customer->tags ?? '—' }}</p>
            </div>
            <div><strong>Assigned</strong>
                <p>{{ $customer->assignedUser?->name ?? 'Unassigned' }}</p>
            </div>
            <div><strong>Follow-up</strong>
                <p class="{{ $overdue ? 'font-semibold text-red-600' : '' }}">
                    {{ $customer->next_follow_up_at?->format('d M Y H:i') ?? '—' }}
                    @if ($overdue)
                        (Overdue)
                    @endif
                </p>
            </div>
            <div class="sm:col-span-2"><strong>Notes</strong>
                <p class="whitespace-pre-wrap">{{ $customer->notes ?? '—' }}</p>
            </div>
        </div>

        <a
    href="{{ route('growya.crm.advanced.show', [$business, $customer->id]) }}"
    class="inline-flex rounded-lg bg-emerald-700 px-4 py-2 text-white"
>
    View Customer 360°
</a>

        <h2 class="text-xl font-bold">Add Activity</h2>
        <form method="POST" action="{{ route('growya.crm.note', [$business, $customer->id]) }}"
            class="space-y-3 rounded-xl border p-5 dark:border-zinc-700">
            @csrf
            <select name="type" class="w-full rounded-lg border p-2 dark:bg-zinc-900">
                @foreach (['note' => 'Note', 'call' => 'Call', 'meeting' => 'Meeting', 'follow_up' => 'Follow-up'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <textarea required maxlength="5000" name="description" rows="3"
                class="w-full rounded-lg border p-2 dark:bg-zinc-900" placeholder="What happened?">{{ old('description') }}</textarea>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Save activity</button>
        </form>

        <h2 class="text-xl font-bold">Activity Timeline</h2>
        <div class="space-y-3">
            @forelse($customer->activities as $a)
                <div class="rounded-xl border p-4 dark:border-zinc-700">
                    <div class="flex justify-between">
                        <strong>{{ ucfirst(str_replace('_', ' ', $a->type)) }}</strong>
                        <span class="text-xs text-zinc-500">{{ $a->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap">{{ $a->description }}</p>
                    <p class="text-xs text-zinc-500">{{ $a->actor?->name ?? 'System' }}</p>
                </div>
            @empty
                <p>No activity recorded yet.</p>
            @endforelse
        </div>

        <a href="{{ route('growya.crm.index', $business) }}" class="inline-block text-emerald-700 underline">Back to
            directory</a>
    </div>
</x-layouts::app>
