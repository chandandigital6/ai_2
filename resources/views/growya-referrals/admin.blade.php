<x-layouts::app :title="'Referral Administration'">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Referral & Rewards Administration</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage referral program settings, points, and business approvals</p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm font-medium text-red-800 dark:bg-red-950/50 dark:border-red-900 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Stats Grid --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Referred Users</span>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $users }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Approved Referred Businesses</span>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $businesses }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Total Points Credited</span>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($points) }}</p>
            </div>
        </div>

        {{-- Settings Form Card --}}
        <div class="rounded-3xl border border-zinc-200 bg-white p-6 sm:p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-5">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Program Reward Settings</h2>

            <form method="POST" action="{{ route('growya.referrals.settings') }}" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4 items-end">
                @csrf
                @method('PATCH')

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Customer Registration Points</label>
                    <input class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 shadow-2xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                           type="number" name="customer_points" value="{{ old('customer_points', $settings?->customer_points ?? 20) }}" min="0" max="100000" required>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Approved Business Points</label>
                    <input class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 shadow-2xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500"
                           type="number" name="business_points" value="{{ old('business_points', $settings?->business_points ?? 100) }}" min="0" max="100000" required>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Program Status</label>
                    <select class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 shadow-2xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:border-emerald-500 focus:ring-emerald-500" name="enabled">
                        <option value="1" @selected($settings?->enabled)>Enabled</option>
                        <option value="0" @selected(!$settings?->enabled)>Disabled</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 transition-colors">
                        Save Rules
                    </button>
                </div>
            </form>
        </div>

        {{-- Referral History & Approval Table --}}
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Referral History & Approvals</h2>

            <div class="overflow-hidden rounded-3xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="p-4">Referrer</th>
                                <th class="p-4">Referred User</th>
                                <th class="p-4">Code</th>
                                <th class="p-4">Customer Status</th>
                                <th class="p-4">Business Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($referrals as $r)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="p-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ $r->referrer?->name ?? 'N/A' }}</td>
                                    <td class="p-4 text-zinc-700 dark:text-zinc-300">{{ $r->referredUser?->name ?? 'N/A' }}</td>
                                    <td class="p-4 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $r->referral_code }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $r->customer_rewarded_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' }}">
                                            {{ $r->customer_rewarded_at ? 'Credited' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $r->business_rewarded_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' }}">
                                            {{ $r->business_rewarded_at ? 'Credited' : 'Pending Approval' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        @if(!$r->business_rewarded_at && Route::has('growya.referrals.approve'))
                                            <form method="POST" action="{{ route('growya.referrals.approve', $r) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 transition-colors shadow-2xs">
                                                    Approve Business
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-zinc-400 font-medium">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-zinc-500 dark:text-zinc-400 italic">No referrals found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($referrals->hasPages())
                <div class="pt-2">{{ $referrals->links() }}</div>
            @endif
        </div>

    </div>
</x-layouts::app>
