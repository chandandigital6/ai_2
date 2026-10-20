<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaCrmActivity;
use App\Models\GrowyaCrmContact;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaCrmStaffController extends Controller
{
    private const STATUSES = ['new', 'contacted', 'interested', 'converted', 'closed'];

    private const PERIODS = ['daily', 'weekly', 'monthly'];

    private function access(Request $request, Business $business, bool $manager = false): void
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'active', 403);
        if ($user->hasRole('super_admin', 'web')) {
            return;
        }
        abort_unless($business->status === 'active' && $business->verification_status === 'approved', 403);
        if ((int) $business->owner_id === (int) $user->id) {
            return;
        }
        abort_if($manager && ! $this->isManager($request, $business), 403);
        $member = DB::table('business_users')->where('business_id', $business->id)
            ->where('user_id', $user->id)->where('is_active', true)->exists();
        abort_unless($member && app(BusinessAccessService::class)->can($user, $business, 'customers.view'), 403);
    }

    private function isManager(Request $request, Business $business): bool
    {
        return app(BusinessAccessService::class)->can($request->user(), $business, 'customers.assign');
    }

    private function staff(Business $business)
    {
        return DB::table('business_users')->join('users', 'users.id', '=', 'business_users.user_id')
            ->where('business_users.business_id', $business->id)->where('business_users.is_active', true)
            ->where('users.status', 'active')->where('users.id', '!=', $business->owner_id)
            ->select('users.id', 'users.name')->distinct()->orderBy('users.name')->get();
    }

    private function contact(Request $request, Business $business, int $id): GrowyaCrmContact
    {
        $q = GrowyaCrmContact::where('business_id', $business->id)->whereKey($id);
        if (! $this->isManager($request, $business)) {
            $q->where('assigned_to', $request->user()->id);
        }

        return $q->firstOrFail();
    }

    private function audit(Request $request, Business $business, GrowyaCrmContact $contact, string $type, string $message): void
    {
        GrowyaCrmActivity::create([
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'actor_id' => $request->user()->id,
            'type' => $type,
            'description' => $message,
        ]);
    }

    public function dashboard(Request $request, Business $business)
    {
        $this->access($request, $business);
        $manager = $this->isManager($request, $business);
        $q = GrowyaCrmContact::query()->where('business_id', $business->id);
        if (! $manager) {
            $q->where('assigned_to', $request->user()->id);
        }
        if ($manager && $request->filled('staff_id')) {
            $staffId = (int) $request->input('staff_id');
            abort_unless($this->staff($business)->contains('id', $staffId), 422);
            $q->where('assigned_to', $staffId);
        }
        $totals = (clone $q)->selectRaw('status,COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $due = (clone $q)->whereNotIn('status', ['converted', 'closed'])->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now())->count();
        $leads = (clone $q)->with('assignedUser:id,name')->latest('id')->paginate(20)->withQueryString();
        $staff = $manager ? $this->staff($business) : collect();
        $targets = DB::table('growya_crm_staff_targets')->where('business_id', $business->id)
            ->when(! $manager, fn ($x) => $x->where('user_id', $request->user()->id))
            ->orderByDesc('period_start')->limit(30)->get();

        return view('growya-crm-staff.dashboard', compact('business', 'manager', 'totals', 'due', 'leads', 'staff', 'targets'));
    }

    public function show(Request $request, Business $business, int $contact)
    {
        $this->access($request, $business);
        $customer = $this->contact($request, $business, $contact);
        $customer->load(['assignedUser:id,name', 'activities.actor:id,name']);
        $staff = $this->isManager($request, $business) ? $this->staff($business) : collect();
        $manager = $this->isManager($request, $business);

        return view('growya-crm-staff.show', compact('business', 'customer', 'staff', 'manager'));
    }

    public function assign(Request $request, Business $business, int $contact)
    {
        $this->access($request, $business, true);
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', Rule::exists('business_users', 'user_id')
            ->where('business_id', $business->id)->where('is_active', true)]]);
        $target = $data['assigned_to'] ?? null;
        if ($target !== null) {
            abort_unless(DB::table('users')->where('id', $target)->where('status', 'active')->exists(), 422);
            abort_if((int) $target === (int) $business->owner_id, 422);
        }
        DB::transaction(function () use ($request, $business, $contact, $target) {
            $c = GrowyaCrmContact::where('business_id', $business->id)->whereKey($contact)->lockForUpdate()->firstOrFail();
            if ((string) $c->assigned_to === (string) $target) {
                return;
            }
            $before = $c->assigned_to;
            $c->update(['assigned_to' => $target]);
            $this->audit($request, $business, $c, 'assignment', 'Assigned staff changed from '.($before ?? 'unassigned').' to '.($target ?? 'unassigned'));
        });

        return back()->with('success', 'Lead assignment updated.');
    }

    public function progress(Request $request, Business $business, int $contact)
    {
        $this->access($request, $business);
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'next_follow_up_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);
        DB::transaction(function () use ($request, $business, $contact, $data) {
            $c = GrowyaCrmContact::where('business_id', $business->id)->whereKey($contact)->lockForUpdate()->firstOrFail();
            abort_unless($this->isManager($request, $business) || (int) $c->assigned_to === (int) $request->user()->id, 403);
            $old = $c->status;
            $oldDue = $c->next_follow_up_at?->format('Y-m-d H:i:s');
            $c->update(['status' => $data['status'], 'next_follow_up_at' => $data['next_follow_up_at'] ?? null]);
            $newDue = $c->next_follow_up_at?->format('Y-m-d H:i:s');
            if ($old !== $c->status || $oldDue !== $newDue) {
                $this->audit($request, $business, $c, 'progress', "Status: {$old} -> {$c->status}; follow-up: ".($oldDue ?? 'none').' -> '.($newDue ?? 'none'));
            }
            if (! empty($data['note'])) {
                $this->audit($request, $business, $c, 'note', $data['note']);
            }
        });

        return back()->with('success', 'Lead progress saved.');
    }

    public function activity(Request $request, Business $business, int $contact)
    {
        $this->access($request, $business);
        $data = $request->validate([
            'type' => ['required', Rule::in(['call', 'meeting', 'follow_up', 'note'])],
            'description' => ['required', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($request, $business, $contact, $data) {
            $c = $this->contact($request, $business, $contact);
            $this->audit($request, $business, $c, $data['type'], $data['description']);
        });

        return back()->with('success', 'Activity recorded.');
    }

    public function target(Request $request, Business $business)
    {
        $this->access($request, $business, true);
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('business_users', 'user_id')->where('business_id', $business->id)->where('is_active', true)],
            'period' => ['required', Rule::in(self::PERIODS)],
            'period_start' => ['required', 'date_format:Y-m-d'],
            'calls_target' => ['required', 'integer', 'min:0', 'max:100000'],
            'followups_target' => ['required', 'integer', 'min:0', 'max:100000'],
            'conversions_target' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
        abort_unless(DB::table('users')->where('id', $data['user_id'])->where('status', 'active')->exists(), 422);
        abort_if((int) $data['user_id'] === (int) $business->owner_id, 422);
        $start = Carbon::parse($data['period_start'])->startOfDay();
        if ($data['period'] === 'weekly') {
            $start->startOfWeek();
        }
        if ($data['period'] === 'monthly') {
            $start->startOfMonth();
        }
        DB::table('growya_crm_staff_targets')->updateOrInsert(
            ['business_id' => $business->id, 'user_id' => $data['user_id'], 'period' => $data['period'], 'period_start' => $start->toDateString()],
            [
                'calls_target' => $data['calls_target'],
                'followups_target' => $data['followups_target'],
                'conversions_target' => $data['conversions_target'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Staff target saved.');
    }

    public function report(Request $request, Business $business)
    {
        $this->access($request, $business, true);
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();
        abort_if($from->diffInDays($to) > 366, 422, 'Maximum report range is 366 days.');
        $staff = $this->staff($business);
        $reports = $staff->map(function ($member) use ($business, $from, $to) {
            $base = GrowyaCrmContact::where('business_id', $business->id)->where('assigned_to', $member->id);
            $activity = GrowyaCrmActivity::where('business_id', $business->id)->where('actor_id', $member->id)
                ->whereBetween('created_at', [$from, $to]);

            return (object) [
                'id' => $member->id,
                'name' => $member->name,
                'assigned' => (clone $base)->count(),
                'converted_current' => (clone $base)->where('status', 'converted')->count(),
                'calls' => (clone $activity)->where('type', 'call')->count(),
                'followups' => (clone $activity)->where('type', 'follow_up')->count(),
                'meetings' => (clone $activity)->where('type', 'meeting')->count(),
            ];
        });

        return view('growya-crm-staff.report', compact('business', 'reports', 'from', 'to'));
    }
}
