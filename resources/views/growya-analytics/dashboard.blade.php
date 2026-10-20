<x-layouts::app :title="$business ? 'Business Analytics' : 'Platform Analytics'" :business="$business">
    @php
        $isAdmin = $business === null;
        $baseRoute = $isAdmin ? 'growya.analytics.admin' : 'growya.analytics.owner';
        $exportRoute = $isAdmin ? 'growya.analytics.admin.export' : 'growya.analytics.owner.export';
        $params = $isAdmin ? [] : ['business' => $business];
        $filters = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
        $money = fn($v) => '₹' . number_format((float) $v, 2);
        $num = fn($v) => number_format((float) $v);

        // ---- Derived numbers ----
        $avgOrder = $stats['paid_orders'] > 0 ? $stats['paid_sales'] / $stats['paid_orders'] : 0;
        $paidRate = $stats['orders'] > 0 ? ($stats['paid_orders'] / $stats['orders']) * 100 : 0;
        $ctr = $stats['promotion_impressions'] > 0 ? ($stats['promotion_clicks'] / $stats['promotion_impressions']) * 100 : 0;

        // ---- Quick date presets ----
        $preset = fn($from, $to) => route($baseRoute, $params + ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]);
        $presets = [
            'Today' => [now(), now()],
            'Last 7 days' => [now()->subDays(6), now()],
            'Last 30 days' => [now()->subDays(29), now()],
            'This month' => [now()->startOfMonth(), now()],
            'Last month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'This year' => [now()->startOfYear(), now()],
        ];

        // ---- Daily series (missing days filled with zero) ----
        $byDay = $series->keyBy('day');
        $days = collect(\Carbon\CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(function ($d) use ($byDay) {
                $row = $byDay->get($d->format('Y-m-d'));
                return [
                    'label' => $d->format('d M'),
                    'sales' => (float) ($row->paid_sales ?? 0),
                    'orders' => (int) ($row->orders_count ?? 0),
                ];
            })
            ->values();
        $maxSales = max(1, (float) $days->max('sales'));
        $peak = $days->sortByDesc('sales')->first();
        $dayCount = $days->count();

        // ---- Status colours ----
        $statusBar = [
            'pending' => 'bg-amber-500',
            'confirmed' => 'bg-blue-500',
            'shipped' => 'bg-indigo-500',
            'delivered' => 'bg-emerald-600',
            'cancelled' => 'bg-red-500',
        ];
        $statusTotal = max(1, (int) $statuses->sum('total'));

        $card = 'rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900';
        $dateInput = 'rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-700';

        $hero = [
            ['Paid order value', $money($stats['paid_sales']), 'Paid, non-cancelled orders', 'border-t-emerald-600'],
            ['Orders placed', $num($stats['orders']), $num($stats['pending_orders']) . ' pending', 'border-t-blue-500'],
            ['Paid orders', $num($stats['paid_orders']), number_format($paidRate, 1) . '% of orders paid', 'border-t-indigo-500'],
            ['Avg. paid order', $money($avgOrder), 'Paid value ÷ paid orders', 'border-t-amber-500'],
        ];
    @endphp

    <main id="main-content" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-8">

        {{-- Header --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Growya insights</p>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                    {{ $isAdmin ? 'Platform analytics' : $business->name . ' — analytics' }}
                </h1>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                    · Orders, enquiries, subscriptions aur promotions ka report.
                </p>
            </div>

            <a href="{{ route($exportRoute, $params + $filters) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-800">
                ⬇ Export orders CSV
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Filters --}}
        <section class="{{ $card }} space-y-4">
            <div class="flex flex-wrap gap-2" aria-label="Quick ranges">
                @foreach ($presets as $label => [$pFrom, $pTo])
                    @php $active = $pFrom->format('Y-m-d') === $filters['from'] && $pTo->format('Y-m-d') === $filters['to']; @endphp
                    <a href="{{ $preset($pFrom, $pTo) }}" wire:navigate
                        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition
                        {{ $active ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-zinc-300 text-zinc-600 hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700 dark:text-zinc-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route($baseRoute, $params) }}" class="flex flex-wrap items-end gap-3">
                <label class="grid gap-1 text-sm font-medium">From
                    <input required type="date" name="from" value="{{ $filters['from'] }}" class="{{ $dateInput }}">
                </label>
                <label class="grid gap-1 text-sm font-medium">To
                    <input required type="date" name="to" value="{{ $filters['to'] }}" class="{{ $dateInput }}">
                </label>
                <button type="submit"
                    class="rounded-lg bg-zinc-900 px-5 py-2.5 font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900">
                    Apply
                </button>
                <span class="text-xs text-zinc-500">Max 366 days · sales order creation date ke hisaab se.</span>
            </form>
        </section>

        {{-- Hero KPIs --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($hero as [$label, $value, $hint, $accent])
                <div class="{{ $card }} border-t-4 {{ $accent }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $label }}</p>
                    <p class="mt-2 break-words text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">{{ $value }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        {{-- Chart --}}
        <section class="{{ $card }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Paid order value by day</h2>
                    <p class="mt-1 text-xs text-zinc-500">
                        Har din ke created, paid aur non-cancelled orders. Payment settlement date nahi hai.
                    </p>
                </div>
                @if ($peak && $peak['sales'] > 0)
                    <p class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                        Best day: {{ $peak['label'] }} · {{ $money($peak['sales']) }}
                    </p>
                @endif
            </div>

            @if ($stats['orders'] === 0)
                <p class="mt-6 rounded-xl border border-dashed p-10 text-center text-sm text-zinc-500 dark:border-zinc-700">
                    Is date range mein koi order nahi hai.
                </p>
            @else
                <div class="mt-6 flex gap-3">
                    <div class="flex h-56 flex-col justify-between text-right text-[10px] text-zinc-400">
                        <span>{{ $money($maxSales) }}</span>
                        <span>{{ $money($maxSales / 2) }}</span>
                        <span>₹0</span>
                    </div>

                    <div class="min-w-0 flex-1 overflow-x-auto pb-2">
                        <div class="flex h-56 items-end gap-1 border-b border-l border-zinc-200 pl-1 dark:border-zinc-700"
                            style="min-width: {{ $dayCount * 12 }}px">
                            @foreach ($days as $d)
                                @php $h = $d['sales'] > 0 ? max(3, ($d['sales'] / $maxSales) * 100) : 0; @endphp
                                <div class="group relative flex h-full min-w-[8px] flex-1 items-end"
                                    title="{{ $d['label'] }}: {{ $money($d['sales']) }} · {{ $d['orders'] }} {{ \Illuminate\Support\Str::plural('order', $d['orders']) }}">
                                    <div class="w-full rounded-t bg-emerald-500 transition group-hover:bg-emerald-700"
                                        style="height: {{ $h }}%"></div>
                                    @if ($d['sales'] == 0 && $d['orders'] > 0)
                                        <div class="absolute bottom-0 h-1 w-full rounded bg-zinc-300 dark:bg-zinc-600"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2 flex justify-between text-[10px] text-zinc-400">
                            <span>{{ $days->first()['label'] }}</span>
                            @if ($dayCount > 2)
                                <span>{{ $days[intdiv($dayCount, 2)]['label'] }}</span>
                            @endif
                            <span>{{ $days->last()['label'] }}</span>
                        </div>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-400">Bar par mouse le jao (ya tap karo) to din ki detail dikhegi. Grey line = us din order aaye par paid nahi hue.</p>
            @endif
        </section>

        {{-- Secondary stats --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="{{ $card }}">
                <h2 class="mb-3 font-bold">Enquiries</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Product enquiries</dt><dd class="font-semibold">{{ $num($stats['product_enquiries']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Service enquiries</dt><dd class="font-semibold">{{ $num($stats['service_enquiries']) }}</dd></div>
                    <div class="flex justify-between border-t pt-3 dark:border-zinc-700"><dt class="font-semibold">Total</dt><dd class="font-bold">{{ $num($stats['product_enquiries'] + $stats['service_enquiries']) }}</dd></div>
                </dl>
            </section>

            <section class="{{ $card }}">
                <h2 class="mb-3 font-bold">Money records</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Refunds recorded</dt><dd class="font-semibold">{{ $money($stats['refunds_recorded']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Subscription payments<br><span class="text-xs">admin-recorded</span></dt><dd class="font-semibold">{{ $money($stats['subscription_payments_recorded']) }}</dd></div>
                </dl>
            </section>

            <section class="{{ $card }}">
                <h2 class="mb-3 font-bold">Promotions</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Approvals</dt><dd class="font-semibold">{{ $num($stats['promotion_approvals']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Approved amount*</dt><dd class="font-semibold">{{ $money($stats['promotion_amount_verified']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Impressions</dt><dd class="font-semibold">{{ $num($stats['promotion_impressions']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Clicks</dt><dd class="font-semibold">{{ $num($stats['promotion_clicks']) }}</dd></div>
                    <div class="flex justify-between border-t pt-3 dark:border-zinc-700"><dt class="font-semibold">CTR</dt><dd class="font-bold">{{ number_format($ctr, 2) }}%</dd></div>
                </dl>
            </section>
        </div>

        @if ($isAdmin)
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="{{ $card }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">New users</p>
                    <p class="mt-2 text-3xl font-bold">{{ $num($stats['new_users']) }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">New businesses</p>
                    <p class="mt-2 text-3xl font-bold">{{ $num($stats['new_businesses']) }}</p>
                </div>
            </div>
        @endif

        {{-- Products + statuses --}}
        <div class="grid gap-5 lg:grid-cols-3">
            <section class="{{ $card }} overflow-x-auto lg:col-span-2">
                <h2 class="mb-4 text-lg font-bold">Top-selling products <span class="text-sm font-normal text-zinc-500">(paid orders)</span></h2>

                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700">
                            <th class="w-10 pb-2">#</th>
                            <th class="pb-2">Product</th>
                            <th class="pb-2 text-right">Units</th>
                            <th class="pb-2 text-right">Item value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topProducts as $item)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2.5 text-zinc-400">{{ $loop->iteration }}</td>
                                <td class="py-2.5 font-medium">{{ $item->product_name ?? 'Deleted product' }}</td>
                                <td class="py-2.5 text-right">{{ $num($item->units) }}</td>
                                <td class="py-2.5 text-right font-semibold">{{ $money($item->gross_item_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-zinc-500">Is range mein koi paid product nahi mila.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <p class="mt-3 text-xs text-zinc-500">Item value order-level discount, refund aur tax se pehle ki hai.</p>
            </section>

            <section class="{{ $card }}">
                <h2 class="mb-4 text-lg font-bold">Order status</h2>

                <div class="space-y-4">
                    @forelse ($statuses as $status)
                        @php $pct = ($status->total / $statusTotal) * 100; @endphp
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span>{{ ucfirst($status->status) }}</span>
                                <span class="font-semibold">{{ $num($status->total) }} <span class="font-normal text-zinc-400">· {{ number_format($pct, 0) }}%</span></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full {{ $statusBar[$status->status] ?? 'bg-zinc-400' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Koi record nahi.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Business performance --}}
        @if ($isAdmin)
            @php $topSales = max(1, (float) $businessPerformance->max('paid_sales')); @endphp
            <section class="{{ $card }} overflow-x-auto">
                <h2 class="mb-4 text-lg font-bold">Business performance <span class="text-sm font-normal text-zinc-500">(top 15)</span></h2>

                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700">
                            <th class="w-10 pb-2">#</th>
                            <th class="pb-2">Business</th>
                            <th class="pb-2 text-right">Orders</th>
                            <th class="pb-2 pl-4">Share</th>
                            <th class="pb-2 text-right">Paid order value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($businessPerformance as $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="py-2.5 text-zinc-400">{{ $loop->iteration }}</td>
                                <td class="py-2.5 font-medium">{{ $row->name }}</td>
                                <td class="py-2.5 text-right">{{ $num($row->order_count) }}</td>
                                <td class="py-2.5 pl-4">
                                    <div class="h-2 w-32 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ ((float) $row->paid_sales / $topSales) * 100 }}%"></div>
                                    </div>
                                </td>
                                <td class="py-2.5 text-right font-semibold">{{ $money($row->paid_sales) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-zinc-500">Is range mein kisi business ke order nahi hain.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @endif

        {{-- Definitions --}}
        <details class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
            <summary class="cursor-pointer text-sm font-semibold">Reporting definitions (padhna zaroori hai)</summary>
            <div class="mt-3 space-y-2">
                <p><strong>Paid order value</strong> marketplace sales hai, Growya ki apni revenue nahi.</p>
                <p><strong>Subscription payments</strong> (<code>admin_recorded</code>) admin ke manual records hain, bank se reconcile nahi hain.</p>
                <p><strong>*Promotion amount</strong> package ki <em>current</em> price se nikla hai, kyunki schema mein price snapshot save nahi hota. Ise audited revenue na maanein.</p>
                <p><strong>Promotion clicks/impressions</strong> selected period mein bani promotions ke lifetime counters hain, event-date analytics nahi.</p>
                <p><strong>Refund records</strong> manually record kiye gaye refunds dikhate hain, confirmed bank transfer nahi.</p>
            </div>
        </details>
    </main>
</x-layouts::app>
