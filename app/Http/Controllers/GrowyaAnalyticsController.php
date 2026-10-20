<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GrowyaAnalyticsController extends Controller
{
    public function __construct(private BusinessAccessService $access) {}

    private function dates(Request $request): array
    {
        $d = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = Carbon::parse($d['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($d['to'] ?? now()->toDateString())->endOfDay();
        if ($from->gt($to) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Choose a date range of at most 366 days, with From before To.']);
        }

        return [$from, $to];
    }

    private function authorizeOwner(Request $request, Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'analytics.view'), 403);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin', 'web'), 403);
    }

    private function report(Carbon $from, Carbon $to, ?Business $business): array
    {
        $range = fn ($q, $col = 'created_at') => $q->whereBetween($col, [$from, $to]);
        $scope = function ($q, string $column = 'business_id') use ($business) {
            return $business ? $q->where($column, $business->id) : $q;
        };

        $orders = $scope($range(DB::table('orders')->whereNull('deleted_at')));
        $paid = $scope($range(DB::table('orders')->whereNull('deleted_at')))
            ->where('payment_status', 'paid')->where('status', '!=', 'cancelled');
        $stats = [
            'orders' => (clone $orders)->count(),
            'paid_orders' => (clone $paid)->count(),
            'paid_sales' => round((float) (clone $paid)->sum('total_amount'), 2),
            'pending_orders' => (clone $orders)->where('status', 'pending')->count(),
            'product_enquiries' => $scope($range(DB::table('business_enquiries')))->count(),
            'service_enquiries' => $scope($range(DB::table('service_enquiries')))->count(),
            'refunds_recorded' => round((float) $scope($range(DB::table('order_refund_records')->join('orders', 'orders.id', '=', 'order_refund_records.order_id'), 'order_refund_records.created_at'), 'orders.business_id')
                ->sum('order_refund_records.amount'), 2),
        ];
        // Payments in subscriptions are manual admin records, not independently reconciled receipts.
        $subscriptionPayments = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->whereBetween('subscription_payments.paid_at', [$from, $to])
            ->where('subscription_payments.status', 'admin_recorded');
        if ($business) {
            $subscriptionPayments->where('subscriptions.business_id', $business->id);
        }
        $stats['subscription_payments_recorded'] = round((float) $subscriptionPayments->sum('subscription_payments.amount'), 2);
        $promotions = $scope(DB::table('growya_paid_promotions'))
            ->whereBetween('reviewed_at', [$from, $to])
            ->where('payment_status', 'verified');
        $stats['promotion_approvals'] = (clone $promotions)->count();
        $stats['promotion_amount_verified'] = round((float) (clone $promotions)
            ->join('growya_promotion_packages', 'growya_promotion_packages.id', '=', 'growya_paid_promotions.package_id')
            ->sum('growya_promotion_packages.price'), 2);
        $stats['promotion_clicks'] = (int) $scope($range(DB::table('growya_paid_promotions')))->sum('clicks');
        $stats['promotion_impressions'] = (int) $scope($range(DB::table('growya_paid_promotions')))->sum('impressions');
        $stats['new_users'] = $business ? null : $range(DB::table('users'))->count();
        $stats['new_businesses'] = $business ? null : $range(DB::table('businesses'))->count();

        $series = $scope($range(DB::table('orders')->whereNull('deleted_at')))
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS orders_count')
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' AND status != 'cancelled' THEN total_amount ELSE 0 END) AS paid_sales")
            ->groupByRaw('DATE(created_at)')->orderBy('day')->get();
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.payment_status', 'paid')->where('orders.status', '!=', 'cancelled')
            ->when($business, fn ($q) => $q->where('orders.business_id', $business->id))
            ->selectRaw('order_items.product_id, MAX(order_items.product_name) AS product_name, SUM(order_items.quantity) AS units, SUM(order_items.total_price) AS gross_item_value')
            ->groupBy('order_items.product_id')->orderByDesc('units')->limit(10)->get();
        $statuses = $scope($range(DB::table('orders')->whereNull('deleted_at')))
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->orderByDesc('total')->get();
        $businessPerformance = $business ? collect() : DB::table('orders')
            ->join('businesses', 'businesses.id', '=', 'orders.business_id')
            ->whereNull('orders.deleted_at')->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw("businesses.id, businesses.name, COUNT(orders.id) AS order_count, SUM(CASE WHEN orders.payment_status = 'paid' AND orders.status != 'cancelled' THEN orders.total_amount ELSE 0 END) AS paid_sales")
            ->groupBy('businesses.id', 'businesses.name')->orderByDesc('paid_sales')->limit(15)->get();

        return compact('stats', 'series', 'topProducts', 'statuses', 'businessPerformance');
    }

    public function owner(Request $request, Business $business)
    {
        $this->authorizeOwner($request, $business);
        [$from, $to] = $this->dates($request);

        return view('growya-analytics.dashboard', array_merge(
            $this->report($from, $to, $business),
            compact('from', 'to', 'business')
        ));
    }

    public function admin(Request $request)
    {
        $this->authorizeAdmin($request);
        [$from, $to] = $this->dates($request);
        $business = null;

        return view('growya-analytics.dashboard', array_merge(
            $this->report($from, $to, null),
            compact('from', 'to', 'business')
        ));
    }

    public function ownerExport(Request $request, Business $business): StreamedResponse
    {
        $this->authorizeOwner($request, $business);
        [$from, $to] = $this->dates($request);

        return $this->csv($from, $to, $business);
    }

    public function adminExport(Request $request): StreamedResponse
    {
        $this->authorizeAdmin($request);
        [$from, $to] = $this->dates($request);

        return $this->csv($from, $to, null);
    }

    private function csv(Carbon $from, Carbon $to, ?Business $business): StreamedResponse
    {
        $name = 'growya-orders-'.($business?->id ?? 'platform').'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($from, $to, $business) {
            $fp = fopen('php://output', 'w');
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, ['Order ID', 'Order Number', 'Business ID', 'Created At', 'Status', 'Payment Status', 'Subtotal', 'Discount', 'Tax', 'Total']);
            $q = DB::table('orders')->whereNull('deleted_at')->whereBetween('created_at', [$from, $to]);
            if ($business) {
                $q->where('business_id', $business->id);
            }
            $q->orderBy('id')->chunk(500, function ($orders) use ($fp) {
                foreach ($orders as $o) {
                    $safe = static function ($value) {
                        $v = (string) ($value ?? '');

                        // Prevent spreadsheet formula injection from user-provided data.
                        return preg_match('/^\s*[=+@\-\t\r]/u', $v) ? "'".$v : $v;
                    };
                    fputcsv($fp, [$o->id, $safe($o->order_number), $o->business_id, $o->created_at, $safe($o->status), $safe($o->payment_status), $o->subtotal, $o->discount_amount, $o->tax_amount, $o->total_amount]);
                }
            });
            fclose($fp);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
