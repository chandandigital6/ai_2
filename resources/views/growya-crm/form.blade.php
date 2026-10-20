<x-layouts::app :title="$contact ? 'Edit Customer' : 'Add Customer'">
    <div class="mx-auto max-w-4xl space-y-5 px-5 py-8">
        <h1 class="text-2xl font-bold">{{ $contact ? 'Edit' : 'New' }} CRM Customer — {{ $business->name }}</h1>

        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-800">
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ $contact ? route('growya.crm.update', [$business, $contact->id]) : route('growya.crm.store', $business) }}"
            class="grid gap-4 rounded-xl border p-5 dark:border-zinc-700 sm:grid-cols-2">
            @csrf
            @if ($contact)
                @method('PATCH')
            @endif

            @foreach ([
        'name' => ['Name *', 150],
        'email' => ['Email', 190],
        'phone' => ['Phone', 30],
        'company' => ['Company', 190],
        'tags' => ['Tags (comma-separated)', 500],
    ] as $field => [$label, $max])
                <label class="block text-sm font-semibold">{{ $label }}
                    <input class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-900" name="{{ $field }}"
                        maxlength="{{ $max }}" value="{{ old($field, $contact?->{$field}) }}"
                        @if ($field === 'name') required @endif
                        @if ($field === 'email') type="email" @endif>
                </label>
            @endforeach

            <label class="text-sm font-semibold">Status
                <select class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-900" name="status">
                    @foreach (['new', 'contacted', 'interested', 'converted', 'closed'] as $v)
                        <option value="{{ $v }}" @selected(old('status', $contact?->status ?? 'new') === $v)>{{ ucfirst($v) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold">Source
                <select class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-900" name="source">
                    @foreach (['manual', 'import', 'product_enquiry', 'service_enquiry', 'wishlist', 'chat', 'other'] as $v)
                        <option value="{{ $v }}" @selected(old('source', $contact?->source ?? 'manual') === $v)>
                            {{ ucfirst(str_replace('_', ' ', $v)) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold">Assign to
                <select class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-900" name="assigned_to">
                    <option value="">Unassigned</option>
                    @foreach ($assignees as $a)
                        <option value="{{ $a->id }}" @selected((string) old('assigned_to', $contact?->assigned_to) === (string) $a->id)>{{ $a->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold">Next Follow-up
                <input class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-900" name="next_follow_up_at"
                    type="datetime-local"
                    value="{{ old('next_follow_up_at', $contact?->next_follow_up_at?->format('Y-m-d\TH:i')) }}">
            </label>

            <label class="text-sm font-semibold sm:col-span-2">Notes
                <textarea name="notes" rows="5" maxlength="10000" class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-900">{{ old('notes', $contact?->notes) }}</textarea>
            </label>

            <div class="flex gap-3 sm:col-span-2">
                <button class="rounded-lg bg-emerald-700 px-5 py-2 text-white">Save customer</button>
                <a href="{{ $contact ? route('growya.crm.show', [$business, $contact->id]) : route('growya.crm.index', $business) }}"
                    class="rounded-lg border px-5 py-2">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts::app>
