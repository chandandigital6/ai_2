<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GrowyaLeadInsightsController extends Controller
{
    private function dates(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:all,product_enquiry,service_enquiry,wishlist,chat'],
            'status' => ['nullable', 'in:all,new,contacted,follow_up,converted,closed'],
        ]);
        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();
        if ($from->gt($to) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Choose a valid range of up to 366 days.']);
        }

        return [$from, $to, $validated['source'] ?? 'all', $validated['status'] ?? 'all'];
    }

    private function ownerOnly(Request $request, Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'leads.view'), 403);
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin', 'web'), 403);
    }

    /** All sources expose a common reporting projection; source tables are not mutated. */
    private function leads(?int $businessId, Carbon $from, Carbon $to, string $source = 'all', string $status = 'all')
    {
        $scope = function ($query, string $dateColumn, string $businessColumn = 'business_id') use ($businessId, $from, $to) {
            $query->whereBetween($dateColumn, [$from, $to]);
            if ($businessId !== null) {
                $query->where($businessColumn, $businessId);
            }

            return $query;
        };

        $parts = [];
        if ($source === 'all' || $source === 'product_enquiry') {
            $parts[] = $scope(DB::table('business_enquiries')->whereNotNull('product_id'), 'created_at')
                ->selectRaw("'product_enquiry' as source, id as source_id, business_id, user_id as customer_id, product_id as product_id, name as customer_name, status as raw_status, CAST(NULL AS DATETIME) as next_followup_at, CAST(NULL AS UNSIGNED) as assigned_user_id, created_at");
        }
        if ($source === 'all' || $source === 'service_enquiry') {
            $parts[] = $scope(DB::table('service_enquiries'), 'created_at')
                ->selectRaw("'service_enquiry' as source, id as source_id, business_id, user_id as customer_id, CAST(NULL AS UNSIGNED) as product_id, customer_name, status as raw_status, CAST(NULL AS DATETIME) as next_followup_at, CAST(NULL AS UNSIGNED) as assigned_user_id, created_at");
        }
        if ($source === 'all' || $source === 'wishlist') {
            $parts[] = $scope(DB::table('wishlist_interests'), 'created_at')
                ->selectRaw("'wishlist' as source, id as source_id, business_id, user_id as customer_id, CASE WHEN item_type = 'product' THEN item_id ELSE NULL END as product_id, CAST(NULL AS CHAR(255)) as customer_name, status as raw_status, follow_up_at as next_followup_at, updated_by as assigned_user_id, created_at");
        }
        if ($source === 'all' || $source === 'chat') {
            $parts[] = $scope(DB::table('growya_chat_threads'), 'created_at')
                ->selectRaw("'chat' as source, id as source_id, business_id, customer_id, CASE WHEN context_type = 'product' THEN context_id ELSE NULL END as product_id, CAST(NULL AS CHAR(255)) as customer_name, followup_status as raw_status, followup_at as next_followup_at, assigned_to as assigned_user_id, created_at");
        }
        $base = array_shift($parts);
        foreach ($parts as $part) {
            $base->unionAll($part);
        }
        $query = DB::query()->fromSub($base, 'lead_rows');
        if ($status !== 'all') {
            $aliases = [
                'new' => ['new', 'pending', 'open'],
                'contacted' => ['contacted', 'in_progress'],
                'follow_up' => ['follow_up', 'follow-up', 'followup'],
                'converted' => ['converted'],
                'closed' => ['closed', 'rejected', 'cancelled'],
            ];
            $query->whereIn('raw_status', $aliases[$status]);
        }

        return $query;
    }

    private function report(Request $request, ?Business $business, bool $admin)
    {
        [$from, $to, $source, $status] = $this->dates($request);
        $base = $this->leads($business?->id, $from, $to, $source, $status);
        $rows = (clone $base)->orderByDesc('created_at')->orderByDesc('source_id')->paginate(30)->withQueryString();
        $total = (clone $base)->count();
        $converted = (clone $base)->where('raw_status', 'converted')->count();
        $bySource = (clone $base)->select('source')->selectRaw('COUNT(*) as leads')->groupBy('source')->orderBy('source')->get();
        $byStatus = (clone $base)->select('raw_status')->selectRaw('COUNT(*) as leads')->groupBy('raw_status')->orderByDesc('leads')->get();
        $byBusiness = $admin
            ? (clone $base)->select('business_id')->selectRaw('COUNT(*) as leads')->groupBy('business_id')->orderByDesc('leads')->limit(15)->get()
            : collect();
        $names = DB::table('businesses')->whereIn('id', $byBusiness->pluck('business_id')->all())->pluck('name', 'id');
        $overdue = (clone $base)->whereNotNull('next_followup_at')->where('next_followup_at', '<', now())
            ->whereNotIn('raw_status', ['closed', 'converted', 'cancelled', 'rejected'])->count();
        $staff = (clone $base)->whereNotNull('assigned_user_id')->select('assigned_user_id')
            ->selectRaw('COUNT(*) as handled, SUM(CASE WHEN raw_status = ? THEN 1 ELSE 0 END) as converted', ['converted'])
            ->groupBy('assigned_user_id')->orderByDesc('handled')->limit(15)->get();
        $staffNames = DB::table('users')->whereIn('id', $staff->pluck('assigned_user_id')->all())->pluck('name', 'id');
        $topProducts = (clone $base)->whereNotNull('product_id')->select('product_id')
            ->selectRaw('COUNT(*) as interests')->groupBy('product_id')->orderByDesc('interests')->limit(10)->get();
        $productNames = DB::table('products')->whereIn('id', $topProducts->pluck('product_id')->all())->pluck('name', 'id');

        return view('growya-lead-insights.dashboard', compact('business', 'admin', 'from', 'to', 'source', 'status', 'rows', 'total', 'converted', 'bySource', 'byStatus', 'byBusiness', 'names', 'overdue', 'staff', 'staffNames', 'topProducts', 'productNames'));
    }

    public function owner(Request $request, Business $business)
    {
        $this->ownerOnly($request, $business);

        return $this->report($request, $business, false);
    }

    public function admin(Request $request)
    {
        $this->adminOnly($request);

        return $this->report($request, null, true);
    }

    private function export(Request $request, ?Business $business): StreamedResponse
    {
        [$from, $to, $source, $status] = $this->dates($request);
        $query = $this->leads($business?->id, $from, $to, $source, $status)->orderBy('created_at')->orderBy('source_id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['source', 'source_id', 'business_id', 'customer_id', 'product_id', 'customer_name', 'raw_status', 'assigned_user_id', 'next_followup_at', 'created_at']);
            foreach ($query->cursor() as $r) {
                // Protect spreadsheet viewers against formula injection.
                $values = (array) $r;
                foreach ($values as &$value) {
                    if (is_string($value) && preg_match('/^[\s]*[=+@\-]/u', $value)) {
                        $value = "'".$value;
                    }
                }
                unset($value);
                fputcsv($out, array_values($values));
            }
            fclose($out);
        }, 'growya-lead-insights-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function ownerExport(Request $request, Business $business): StreamedResponse
    {
        $this->ownerOnly($request, $business);

        return $this->export($request, $business);
    }

    public function adminExport(Request $request): StreamedResponse
    {
        $this->adminOnly($request);

        return $this->export($request, null);
    }
}
