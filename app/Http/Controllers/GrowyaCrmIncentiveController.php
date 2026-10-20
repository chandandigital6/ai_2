<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaCrmIncentiveController extends Controller
{
    private function manager(Request $request, Business $business): bool
    {
        return app(BusinessAccessService::class)->can($request->user(), $business, 'incentives.manage');
    }

    private function access(Request $request, Business $business, bool $managerOnly = false): bool
    {
        abort_unless($request->user() && $request->user()->status === 'active', 403);
        $manager = $this->manager($request, $business);
        if ($manager) {
            return true;
        }
        abort_if($managerOnly, 403);
        abort_unless($business->status === 'active' && $business->verification_status === 'approved', 403);
        abort_unless(DB::table('business_users')->where('business_id', $business->id)
            ->where('user_id', $request->user()->id)->where('is_active', true)->exists(), 403);
        abort_unless(app(BusinessAccessService::class)->can($request->user(), $business, 'incentives.view'), 403);

        return false;
    }

    private function staff(Business $business)
    {
        return DB::table('business_users')->join('users', 'users.id', '=', 'business_users.user_id')
            ->where('business_users.business_id', $business->id)->where('business_users.is_active', true)
            ->where('users.status', 'active')->where('users.id', '!=', $business->owner_id)
            ->select('users.id', 'users.name')->distinct()->orderBy('users.name')->get();
    }

    private function audit(Business $business, int $claim, Request $request, string $action, ?string $note = null): void
    {
        DB::table('growya_crm_incentive_audits')->insert([
            'business_id' => $business->id,
            'claim_id' => $claim,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function index(Request $request, Business $business)
    {
        $manager = $this->access($request, $business);
        $staff = $manager ? $this->staff($business) : collect();
        $rules = $manager ? DB::table('growya_crm_incentive_rules')->where('business_id', $business->id)->orderByDesc('id')->get() : collect();
        $q = DB::table('growya_crm_incentive_claims as c')
            ->join('users as u', 'u.id', '=', 'c.staff_user_id')
            ->leftJoin('growya_crm_contacts as lead', function ($j) {
                $j->on('lead.id', '=', 'c.contact_id')->on('lead.business_id', '=', 'c.business_id');
            })
            ->where('c.business_id', $business->id)
            ->select('c.*', 'u.name as staff_name', 'lead.name as contact_name');
        if (! $manager) {
            $q->where('c.staff_user_id', $request->user()->id);
        }
        if ($manager && $request->filled('staff_id')) {
            $id = $request->integer('staff_id');
            abort_unless($staff->contains('id', $id), 422);
            $q->where('c.staff_user_id', $id);
        }
        if ($request->filled('status')) {
            $request->validate(['status' => Rule::in(['pending', 'approved', 'rejected', 'paid'])]);
            $q->where('c.status', $request->input('status'));
        }
        $claims = (clone $q)->orderByDesc('c.id')->paginate(25)->withQueryString();
        $summary = DB::table('growya_crm_incentive_claims')->where('business_id', $business->id)
            ->when(! $manager, fn ($q) => $q->where('staff_user_id', $request->user()->id))
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(incentive_amount),0) as amount')
            ->groupBy('status')->get()->keyBy('status');
        $audits = $manager ? DB::table('growya_crm_incentive_audits as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.actor_id')->where('a.business_id', $business->id)
            ->select('a.*', 'u.name as actor_name')->orderByDesc('a.id')->limit(20)->get() : collect();

        return view('growya-crm-incentives.index', compact('business', 'manager', 'staff', 'rules', 'claims', 'summary', 'audits'));
    }

    public function rule(Request $request, Business $business)
    {
        $this->access($request, $business, true);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'basis' => ['required', Rule::in(['crm_conversion', 'manually_verified_sale'])],
            'calculation' => ['required', Rule::in(['fixed', 'percentage'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ]);
        if ($data['calculation'] === 'percentage' && (float) $data['amount'] > 100) {
            return back()->withErrors(['amount' => 'Percentage cannot exceed 100.'])->withInput();
        }
        DB::table('growya_crm_incentive_rules')->insert([...$data, 'business_id' => $business->id, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Incentive rule added.');
    }

    public function toggleRule(Request $request, Business $business, int $rule)
    {
        $this->access($request, $business, true);
        DB::transaction(function () use ($business, $rule) {
            $r = DB::table('growya_crm_incentive_rules')->where('business_id', $business->id)->where('id', $rule)->lockForUpdate()->first();
            abort_unless($r, 404);
            DB::table('growya_crm_incentive_rules')->where('id', $r->id)->update(['active' => ! $r->active, 'updated_at' => now()]);
        });

        return back()->with('success', 'Rule status updated.');
    }

    public function claim(Request $request, Business $business)
    {
        $this->access($request, $business, true);
        $data = $request->validate([
            'rule_id' => ['required', 'integer'],
            'staff_user_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'base_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'source_reference' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_unless($this->staff($business)->contains('id', (int) $data['staff_user_id']), 422, 'Staff must be an active business member.');
        DB::transaction(function () use ($request, $business, $data) {
            $rule = DB::table('growya_crm_incentive_rules')->where('business_id', $business->id)
                ->where('id', $data['rule_id'])->where('active', true)->lockForUpdate()->first();
            abort_unless($rule, 422, 'Active rule not found.');
            $contact = null;
            if (! empty($data['contact_id'])) {
                $contact = DB::table('growya_crm_contacts')->where('business_id', $business->id)->where('id', $data['contact_id'])->first();
                abort_unless($contact, 422, 'Contact does not belong to this business.');
                abort_unless((int) $contact->assigned_to === (int) $data['staff_user_id'], 422, 'Contact is not assigned to this staff member.');
            }
            if ($rule->basis === 'crm_conversion') {
                abort_unless($contact && $contact->status === 'converted', 422, 'An assigned converted CRM contact is required.');
                abort_if(
                    DB::table('growya_crm_incentive_claims')->where('business_id', $business->id)
                        ->where('contact_id', $contact->id)->where('basis', 'crm_conversion')->whereIn('status', ['pending', 'approved', 'paid'])->exists(),
                    422,
                    'A non-rejected conversion claim already exists for this lead.'
                );
            }
            if ($rule->basis === 'manually_verified_sale') {
                abort_unless(
                    filled($data['source_reference'] ?? null) && isset($data['base_amount']) && (float) $data['base_amount'] > 0,
                    422,
                    'Payment reference and positive verified paid amount are required.'
                );
                abort_if(DB::table('growya_crm_incentive_claims')->where('business_id', $business->id)
                    ->where('basis', 'manually_verified_sale')->where('source_reference', $data['source_reference'])
                    ->whereIn('status', ['pending', 'approved', 'paid'])->exists(), 422, 'This payment reference already has an active claim.');
            }
            if ($rule->calculation === 'percentage') {
                abort_unless(isset($data['base_amount']) && (float) $data['base_amount'] > 0, 422, 'Percentage rule requires a positive base amount.');
            }
            $base = isset($data['base_amount']) ? round((float) $data['base_amount'], 2) : null;
            $amount = $rule->calculation === 'fixed' ? (float) $rule->amount : round($base * (float) $rule->amount / 100, 2);
            abort_unless($amount > 0 && $amount <= 999999999999, 422);
            $claim = DB::table('growya_crm_incentive_claims')->insertGetId([
                'business_id' => $business->id,
                'staff_user_id' => $data['staff_user_id'],
                'contact_id' => $contact?->id,
                'rule_id' => $rule->id,
                'basis' => $rule->basis,
                'status' => 'pending',
                'base_amount' => $base,
                'rate_snapshot' => $rule->amount,
                'calculation_snapshot' => $rule->calculation,
                'incentive_amount' => $amount,
                'source_reference' => $data['source_reference'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit($business, $claim, $request, 'created', 'Claim created; awaiting owner verification and approval.');
        }, 3);

        return back()->with('success', 'Claim created as pending. Verify supporting evidence before approval.');
    }

    public function review(Request $request, Business $business, int $claim)
    {
        $this->access($request, $business, true);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'note' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $business, $claim, $data) {
            $row = DB::table('growya_crm_incentive_claims')->where('business_id', $business->id)->where('id', $claim)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'pending', 422, 'Only pending claims can be reviewed.');
            if ($data['decision'] === 'approve' && $row->basis === 'manually_verified_sale') {
                abort_unless(filled($row->source_reference) && $row->base_amount > 0, 422, 'Check paid amount and payment reference first.');
                abort_unless($request->boolean('payment_verified'), 422, 'Confirm that the payment has been independently verified.');
            }
            DB::table('growya_crm_incentive_claims')->where('id', $claim)->update([
                'status' => $data['decision'] === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit($business, $claim, $request, $data['decision'] === 'approve' ? 'approved' : 'rejected', $data['note'] ?? null);
        }, 3);

        return back()->with('success', 'Claim reviewed.');
    }

    public function pay(Request $request, Business $business, int $claim)
    {
        $this->access($request, $business, true);
        $data = $request->validate(['payment_reference' => ['required', 'string', 'max:160'], 'note' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $business, $claim, $data) {
            $row = DB::table('growya_crm_incentive_claims')->where('business_id', $business->id)->where('id', $claim)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'approved', 422, 'Only approved claims can be marked paid.');
            DB::table('growya_crm_incentive_claims')->where('id', $claim)->update([
                'status' => 'paid',
                'payment_reference' => $data['payment_reference'],
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit($business, $claim, $request, 'paid', $data['note'] ?? null);
        }, 3);

        return back()->with('success', 'Claim marked paid (manual payment record).');
    }

    public function report(Request $request, Business $business)
    {
        $this->access($request, $business, true);
        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();
        abort_if($from->diffInDays($to) > 366, 422, 'Maximum range: 366 days.');
        $staff = $this->staff($business);
        $targets = DB::table('growya_crm_staff_targets')->where('business_id', $business->id)
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])->get();
        $reports = $staff->map(function ($s) use ($business, $from, $to, $targets) {
            $activities = DB::table('growya_crm_activities')->where('business_id', $business->id)
                ->where('actor_id', $s->id)->whereBetween('created_at', [$from, $to]);
            $claims = DB::table('growya_crm_incentive_claims')->where('business_id', $business->id)
                ->where('staff_user_id', $s->id)->whereBetween('created_at', [$from, $to]);

            // Converted counts below are a CURRENT STATUS snapshot, not historic conversion dates.
            return (object) [
                'id' => $s->id,
                'name' => $s->name,
                'calls' => (clone $activities)->where('type', 'call')->count(),
                'followups' => (clone $activities)->where('type', 'follow_up')->count(),
                'conversions_current' => DB::table('growya_crm_contacts')->where('business_id', $business->id)
                    ->where('assigned_to', $s->id)->where('status', 'converted')->count(),
                'targets' => $targets->where('user_id', $s->id),
                'approved' => (clone $claims)->whereIn('status', ['approved', 'paid'])->sum('incentive_amount'),
                'paid' => (clone $claims)->where('status', 'paid')->sum('incentive_amount'),
            ];
        });

        return view('growya-crm-incentives.report', compact('business', 'reports', 'from', 'to'));
    }

    public function export(Request $request, Business $business)
    {
        $this->access($request, $business, true);
        $q = DB::table('growya_crm_incentive_claims as c')->join('users as u', 'u.id', '=', 'c.staff_user_id')
            ->where('c.business_id', $business->id)->orderBy('c.id')
            ->select(
                'c.id',
                'u.name as staff',
                'c.basis',
                'c.status',
                'c.incentive_amount',
                'c.base_amount',
                'c.source_reference',
                'c.payment_reference',
                'c.created_at',
                'c.paid_at'
            );

        return response()->streamDownload(function () use ($q) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['ID', 'Staff', 'Basis', 'Status', 'Incentive Amount', 'Base Amount', 'Source Reference', 'Payment Reference', 'Created', 'Paid']);
            foreach ($q->cursor() as $r) {
                fputcsv($h, [(string) $r->id, $r->staff, $r->basis, $r->status, $r->incentive_amount, $r->base_amount, $r->source_reference, $r->payment_reference, $r->created_at, $r->paid_at]);
            }
            fclose($h);
        }, 'growya-crm-incentives-'.$business->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
