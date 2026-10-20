<x-layouts::app :title="'Staff CRM Lead'">
    <div class="mx-auto max-w-5xl px-5 py-8 space-y-6">
        <a class="text-emerald-700 underline" href="{{ route('growya.crm.staff.dashboard', $business) }}">← Staff CRM</a>
        <h1 class="text-2xl font-bold">{{ $customer->name }}</h1>
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-3 text-green-900">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-900">{{ $errors->first() }}</div>
        @endif
        <div class="rounded-xl border p-5 space-y-1">
            <p>Email: {{ $customer->email ?? '—' }}</p>
            <p>Phone: {{ $customer->phone ?? '—' }}</p>
            <p>Assigned: {{ $customer->assignedUser?->name ?? 'Unassigned' }}</p>
        </div>
        @if ($manager)
            <form class="rounded-xl border p-5 space-y-3" method="POST"
                action="{{ route('growya.crm.staff.assign', [$business, $customer->id]) }}">@csrf @method('PATCH')
                <h2 class="font-semibold">Assign / Transfer Lead</h2><select class="w-full rounded-lg border p-2"
                    name="assigned_to">
                    <option value="">Unassigned</option>
                    @foreach ($staff as $s)
                        <option value="{{ $s->id }}" @selected($customer->assigned_to == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Update Assignment</button>
            </form>
        @endif
        <form class="rounded-xl border p-5 space-y-3" method="POST"
            action="{{ route('growya.crm.staff.progress', [$business, $customer->id]) }}">@csrf @method('PATCH')
            <h2 class="font-semibold">Lead Status & Follow-up</h2>
            <label>Status<select class="block w-full rounded-lg border p-2" name="status">
                    @foreach (['new', 'contacted', 'interested', 'converted', 'closed'] as $status)
                        <option value="{{ $status }}" @selected($customer->status === $status)>{{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Next follow-up<input class="block w-full rounded-lg border p-2" type="datetime-local"
                    name="next_follow_up_at" value="{{ $customer->next_follow_up_at?->format('Y-m-d\TH:i') }}"></label>
            <label>Note
                <textarea class="block w-full rounded-lg border p-2" name="note" rows="3"></textarea>
            </label>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Save Progress</button>
        </form>
        <form class="rounded-xl border p-5 space-y-3" method="POST"
            action="{{ route('growya.crm.staff.activity', [$business, $customer->id]) }}">@csrf
            <h2 class="font-semibold">Call / Meeting / Follow-up Log</h2><select class="w-full rounded-lg border p-2"
                name="type">
                <option value="call">Call</option>
                <option value="meeting">Meeting</option>
                <option value="follow_up">Follow-up</option>
                <option value="note">Note</option>
            </select>
            <textarea class="w-full rounded-lg border p-2" name="description" required maxlength="5000" rows="3"
                placeholder="Describe the activity"></textarea><button class="rounded-lg bg-slate-800 px-4 py-2 text-white">Save
                Activity</button>
        </form>
        <h2 class="text-lg font-semibold">Activity Audit Timeline</h2>
        <div class="space-y-2">
            @forelse($customer->activities as $a)
                <div class="rounded-xl border p-4">
                    <div class="text-xs text-slate-500">{{ $a->created_at->format('d M Y H:i') }} ·
                        {{ $a->actor?->name ?? 'System' }} · {{ $a->type }}</div>
                    <div class="whitespace-pre-wrap">{{ $a->description }}</div>
            </div>@empty<p>No activity yet.</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
