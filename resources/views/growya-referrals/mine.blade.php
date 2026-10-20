<x-layouts::app :title="'My Referrals'">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">My Referrals & Rewards</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Invite friends and businesses to earn reward points</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-900 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- Referral Code Banner Card --}}
        <div class="relative overflow-hidden rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent p-6 sm:p-8 dark:border-emerald-500/20">
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-4">
                <span class="inline-flex items-center rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    Your Exclusive Referral Code
                </span>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-2xl sm:text-3xl font-extrabold tracking-wider font-mono text-zinc-900 dark:text-white bg-white/80 dark:bg-zinc-900/80 px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-2xs">
                        {{ $code }}
                    </span>
                </div>

                <div class="space-y-1">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Your sharing link:</p>
                    <div class="flex items-center gap-2 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-3 text-sm font-mono text-zinc-700 dark:text-zinc-300 break-all shadow-2xs">
                        <span class="truncate">{{ route('register', ['ref' => $code]) }}</span>
                    </div>
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 pt-1">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    Share this link with new users. Points are promotional rewards and cannot be withdrawn as cash.
                </p>
            </div>
        </div>

        {{-- Points Stat Card --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Total Points Earned</span>
                <p class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($points) }}</p>
            </div>
        </div>

        {{-- Referral History Table --}}
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Referral History</h2>
            <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="p-4">User</th>
                                <th class="p-4">Joined Date</th>
                                <th class="p-4">Customer Reward</th>
                                <th class="p-4">Business Reward</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($records as $r)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="p-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ $r->referredUser?->name ?? 'User' }}</td>
                                    <td class="p-4 text-zinc-600 dark:text-zinc-400">{{ $r->created_at->format('d M Y') }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $r->customer_rewarded_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' }}">
                                            {{ $r->customer_rewarded_at ? 'Credited' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $r->business_rewarded_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' }}">
                                            {{ $r->business_rewarded_at ? 'Credited' : 'Pending approval' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-zinc-500 dark:text-zinc-400 italic">No referrals yet. Start sharing your link!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($records->hasPages())
                <div class="pt-2">{{ $records->links() }}</div>
            @endif
        </div>

        {{-- Points Ledger Activity --}}
        <div class="space-y-4 pt-4">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Points Ledger</h2>
            <div class="space-y-2.5">
                @forelse($activity as $a)
                    <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 font-bold">
                                +{{ $a->points }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 capitalize">{{ ucfirst($a->reward_type) }} Referral Reward</p>
                                <p class="text-xs text-zinc-400">{{ \Illuminate\Support\Carbon::parse($a->created_at)->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 rounded-lg">Credited</span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        No reward activity yet.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</x-layouts::app>
