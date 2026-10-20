<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GrowyaFinanceController extends Controller
{
    private function authorizeOwner(Request $request, Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'finance.view'), 403);
    }

    private function period(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = $validated['from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();
        abort_if($from > $to, 422, 'Start date must not exceed end date.');

        return [$from, $to];
    }

    private function categoryQuery(Business $business)
    {
        return DB::table('growya_finance_categories')->where('business_id', $business->id);
    }

    private function entryQuery(Business $business, string $from, string $to)
    {
        return DB::table('growya_finance_entries as e')
            ->leftJoin('growya_finance_categories as c', function ($j) use ($business) {
                $j->on('c.id', '=', 'e.category_id')->where('c.business_id', '=', $business->id);
            })
            ->where('e.business_id', $business->id)->whereNull('e.deleted_at')
            ->whereBetween('e.entry_date', [$from, $to]);
    }

    private function summary(Business $business, string $from, string $to): array
    {
        $manualIncome = (float) $this->entryQuery($business, $from, $to)->where('e.kind', 'income')->sum('e.amount');
        $manualExpenses = (float) $this->entryQuery($business, $from, $to)->where('e.kind', 'expense')->sum('e.amount');
        // Order statuses are not immutable payment receipts: show this as a paid-order indicator only.
        // Exclude cancelled/refunded/returned states, but separately reconciled partial refunds still require accounting.
        $orders = DB::table('orders')->where('business_id', $business->id)->whereNull('deleted_at')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'canceled', 'refunded', 'returned']);
        $paidOrderRevenue = (float) $orders->sum('total_amount');
        // Legacy installs may not have the latest incentives migration installed yet.
        $paidIncentives = Schema::hasTable('growya_crm_incentive_claims')
            ? (float) DB::table('growya_crm_incentive_claims')
                ->where('business_id', $business->id)->where('status', 'paid')
                ->whereNotNull('paid_at')->whereDate('paid_at', '>=', $from)
                ->whereDate('paid_at', '<=', $to)->sum('incentive_amount') : 0.0;

        return compact('manualIncome', 'manualExpenses', 'paidOrderRevenue', 'paidIncentives') + [
            'totalIncome' => $manualIncome + $paidOrderRevenue,
            'totalExpenses' => $manualExpenses + $paidIncentives,
            'indicativeBalance' => $manualIncome + $paidOrderRevenue - $manualExpenses - $paidIncentives,
        ];
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeOwner($request, $business);
        [$from, $to] = $this->period($request);
        $summary = $this->summary($business, $from, $to);
        $categories = $this->categoryQuery($business)->orderBy('kind')->orderBy('name')->get();
        $entries = $this->entryQuery($business, $from, $to)
            ->select('e.*', 'c.name as category_name')->orderByDesc('e.entry_date')->orderByDesc('e.id')->paginate(20)->withQueryString();
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', e.entry_date)"
            : "DATE_FORMAT(e.entry_date,'%Y-%m')";
        $months = $this->entryQuery($business, $from, $to)
            ->selectRaw($monthExpression.' as month, e.kind, SUM(e.amount) as amount')
            ->groupBy('month', 'e.kind')->orderBy('month')->get();

        return view('growya-finance.index', compact('business', 'from', 'to', 'summary', 'categories', 'entries', 'months'));
    }

    public function storeCategory(Request $request, Business $business)
    {
        $this->authorizeOwner($request, $business);
        $v = $request->validate(['name' => ['required', 'string', 'max:100'], 'kind' => ['required', Rule::in(['income', 'expense'])]]);
        $name = trim($v['name']);
        DB::table('growya_finance_categories')->updateOrInsert(
            ['business_id' => $business->id, 'kind' => $v['kind'], 'name' => $name],
            ['updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('success', 'Category saved.');
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeOwner($request, $business);
        $v = $request->validate([
            'kind' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'integer'],
            'payment_method' => ['required', Rule::in(['cash', 'upi', 'bank', 'card', 'cheque', 'other'])],
            'reference' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        if (! empty($v['category_id'])) {
            abort_unless($this->categoryQuery($business)->where('id', $v['category_id'])->where('kind', $v['kind'])->exists(), 422, 'Invalid category.');
        }
        DB::table('growya_finance_entries')->insert([
            'business_id' => $business->id,
            'kind' => $v['kind'],
            'amount' => $v['amount'],
            'entry_date' => $v['entry_date'],
            'category_id' => $v['category_id'] ?? null,
            'payment_method' => $v['payment_method'],
            'reference' => $v['reference'] ?? null,
            'description' => $v['description'] ?? null,
            'source_key' => null,
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Entry saved. Do not re-enter order receipts or paid incentives as manual records.');
    }

    public function update(Request $request, Business $business, int $entry)
    {
        $this->authorizeOwner($request, $business);
        $record = DB::table('growya_finance_entries')->where('business_id', $business->id)->whereNull('deleted_at')->where('id', $entry)->first();
        abort_unless($record && $record->source_key === null, 404);
        $v = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(['cash', 'upi', 'bank', 'card', 'cheque', 'other'])],
            'reference' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        DB::table('growya_finance_entries')->where('business_id', $business->id)->where('id', $entry)->update([
            ...$v,
            'reference' => $v['reference'] ?? null,
            'description' => $v['description'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Entry updated.');
    }

    public function destroy(Request $request, Business $business, int $entry)
    {
        $this->authorizeOwner($request, $business);
        $updated = DB::table('growya_finance_entries')->where('business_id', $business->id)
            ->whereNull('deleted_at')->whereNull('source_key')->where('id', $entry)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
        abort_unless($updated, 404);

        return back()->with('success', 'Entry archived.');
    }

    public function export(Request $request, Business $business): StreamedResponse
    {
        $this->authorizeOwner($request, $business);
        [$from, $to] = $this->period($request);
        $summary = $this->summary($business, $from, $to);

        return response()->streamDownload(function () use ($business, $from, $to, $summary) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Growya financial overview', 'Business ID', $business->id, 'From', $from, 'To', $to]);
            foreach ($summary as $key => $value) {
                fputcsv($out, [$key, number_format($value, 2, '.', '')]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Date', 'Type', 'Category', 'Amount', 'Method', 'Reference', 'Description']);
            $query = $this->entryQuery($business, $from, $to)->select('e.*', 'c.name as category_name')->orderBy('e.id');
            foreach ($query->cursor() as $e) {
                // Prevent CSV formula injection when opened in spreadsheet software.
                $safe = function ($s) {
                    $s = (string) ($s ?? '');

                    return preg_match('/^[\s]*[=+@\-]/u', $s) ? "'".$s : $s;
                };
                fputcsv($out, [$e->entry_date, $e->kind, $safe($e->category_name), $e->amount, $e->payment_method, $safe($e->reference), $safe($e->description)]);
            }
            fclose($out);
        }, 'growya-finance-'.$business->id.'-'.$from.'-'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
