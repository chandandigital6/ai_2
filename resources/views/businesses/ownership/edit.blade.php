<x-layouts::app :title="__('Transfer Ownership')">
    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-7">
        <a href="{{ route('growya.ownership.index') }}" class="text-sm font-semibold text-emerald-600">← All
            businesses</a>
        <div>
            <p class="text-sm font-semibold text-emerald-500">SUPER ADMIN • OWNERSHIP</p>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $business->name }}</h1>
            <p class="text-sm text-zinc-500">Current owner: {{ $business->owner?->name ?? 'Not assigned' }} • User ID
                {{ $business->owner_id ?? '—' }}</p>
        </div>
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('growya.ownership.update', $business) }}"
            class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 sm:p-7">
            @csrf @method('PATCH')
            <input type="hidden" name="expected_owner_id" value="{{ $business->owner_id }}" />
            <div><label for="new_owner_id" class="mb-2 block text-sm font-semibold">New owner (active user)</label>
                <select required id="new_owner_id" name="new_owner_id"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Choose a user</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('new_owner_id') == $user->id)>{{ $user->name }} —
                            {{ $user->email }} (#{{ $user->id }})</option>
                    @endforeach
                </select>
            </div>
            <div><label for="reason" class="mb-2 block text-sm font-semibold">Reason for transfer</label>
                <textarea id="reason" name="reason" required minlength="10" rows="3"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    placeholder="Describe why ownership is changing">{{ old('reason') }}</textarea>
            </div>
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                This action immediately removes the former owner's access to this business. It does not remove their
                global role or access to other businesses.
            </div>
            <div><label for="confirmation" class="mb-2 block text-sm font-semibold">Type <span
                        class="font-mono">{{ $business->name }}</span> to confirm</label>
                <input required id="confirmation" name="confirmation" autocomplete="off"
                    value="{{ old('confirmation') }}"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-3 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" />
            </div>
            <button type="submit" onclick="return confirm('Transfer ownership of this business?')"
                class="rounded-xl bg-rose-600 px-5 py-3 font-semibold text-white">Confirm ownership transfer</button>
        </form>
        <div class="space-y-3">
            <h2 class="text-xl font-bold">Ownership history</h2>
            @forelse($history as $row)
                <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                    <p class="font-semibold">{{ $row->previousOwner?->name ?? 'Unassigned / removed user' }} →
                        {{ $row->newOwner?->name ?? 'Unknown' }}</p>
                    <p class="text-zinc-500">{{ $row->created_at?->format('d M Y, h:i A') }} • By
                        {{ $row->performedBy?->name ?? 'Deleted account' }}</p>
                    <p class="mt-2 whitespace-pre-wrap">{{ $row->reason }}</p>
                </div>
            @empty <p class="text-sm text-zinc-500">No previous ownership transfers.</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
