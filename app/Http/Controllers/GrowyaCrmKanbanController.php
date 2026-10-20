<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaCrmActivity;
use App\Models\GrowyaCrmContact;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaCrmKanbanController extends Controller
{
    private const STAGES = ['new', 'contacted', 'interested', 'converted', 'closed'];

    private const PRIORITIES = ['low', 'medium', 'high'];

    private function manager(Request $request, Business $business): bool
    {
        return app(BusinessAccessService::class)->can($request->user(), $business, 'customers.assign');
    }

    private function authorizeAccess(Request $request, Business $business): bool
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'active', 403);
        if ($user->hasRole('super_admin', 'web')) {
            return true;
        }
        abort_unless($business->status === 'active' && $business->verification_status === 'approved', 403);
        if ((int) $business->owner_id === (int) $user->id) {
            return true;
        }
        $member = DB::table('business_users')->where('business_id', $business->id)
            ->where('user_id', $user->id)->where('is_active', true)->exists();
        abort_unless($member && app(BusinessAccessService::class)->can($user, $business, 'customers.view'), 403);

        return $this->manager($request, $business);
    }

    private function scopedContact(Request $request, Business $business, int $id): GrowyaCrmContact
    {
        $query = GrowyaCrmContact::where('business_id', $business->id)->whereKey($id);
        if (! $this->manager($request, $business)) {
            $query->where('assigned_to', $request->user()->id);
        }

        return $query->firstOrFail();
    }

    public function index(Request $request, Business $business)
    {
        $manager = $this->authorizeAccess($request, $business);
        $data = $request->validate([
            'staff_id' => ['nullable', 'integer'],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'q' => ['nullable', 'string', 'max:120'],
            'overdue' => ['nullable', 'boolean'],
        ]);
        $staff = collect();
        if ($manager) {
            $staff = DB::table('business_users')->join('users', 'users.id', '=', 'business_users.user_id')
                ->where('business_users.business_id', $business->id)->where('business_users.is_active', true)
                ->where('users.status', 'active')->where('users.id', '!=', $business->owner_id)
                ->select('users.id', 'users.name')->distinct()->orderBy('users.name')->get();
        }
        $base = GrowyaCrmContact::query()->where('business_id', $business->id);
        if (! $manager) {
            $base->where('assigned_to', $request->user()->id);
        }
        if ($manager && isset($data['staff_id'])) {
            abort_unless($staff->contains('id', (int) $data['staff_id']), 422);
            $base->where('assigned_to', (int) $data['staff_id']);
        }
        if (! empty($data['priority'])) {
            $base->where('priority', $data['priority']);
        }
        if (! empty($data['q'])) {
            $term = trim($data['q']);
            $base->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        }
        if ($request->boolean('overdue')) {
            $base->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())
                ->whereNotIn('status', ['converted', 'closed']);
        }
        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        // Limit each column for predictable performance; counts show actual totals.
        $columns = [];
        foreach (self::STAGES as $stage) {
            $columns[$stage] = (clone $base)->where('status', $stage)->with('assignedUser:id,name')
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
                ->orderByDesc('updated_at')->limit(60)->get();
        }

        return view('growya-crm-kanban.index', compact('business', 'manager', 'staff', 'columns', 'counts'));
    }

    public function update(Request $request, Business $business, int $contact)
    {
        $this->authorizeAccess($request, $business);
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STAGES)],
            'priority' => ['nullable', Rule::in(self::PRIORITIES)],
            'next_follow_up_at' => ['sometimes', 'nullable', 'date'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);
        $changed = DB::transaction(function () use ($request, $business, $contact, $data) {
            $lead = GrowyaCrmContact::where('business_id', $business->id)->whereKey($contact)
                ->lockForUpdate()->firstOrFail();
            abort_unless($this->manager($request, $business) || (int) $lead->assigned_to === (int) $request->user()->id, 403);
            $before = ['status' => $lead->status, 'priority' => $lead->priority, 'next_follow_up_at' => $lead->next_follow_up_at?->format('Y-m-d H:i:s')];
            $updates = ['status' => $data['status']];
            if (array_key_exists('priority', $data)) {
                $updates['priority'] = $data['priority'];
            }
            if (array_key_exists('next_follow_up_at', $data)) {
                $updates['next_follow_up_at'] = $data['next_follow_up_at'];
            }
            $lead->update($updates);
            $after = ['status' => $lead->status, 'priority' => $lead->priority, 'next_follow_up_at' => $lead->next_follow_up_at?->format('Y-m-d H:i:s')];
            $diff = [];
            foreach ($before as $key => $value) {
                if ($value !== $after[$key]) {
                    $diff[] = "{$key}: ".($value ?? 'none').' -> '.($after[$key] ?? 'none');
                }
            }
            if ($diff) {
                GrowyaCrmActivity::create([
                    'business_id' => $business->id,
                    'contact_id' => $lead->id,
                    'actor_id' => $request->user()->id,
                    'type' => 'progress',
                    'description' => implode('; ', $diff),
                ]);
            }
            if (! empty($data['note'])) {
                GrowyaCrmActivity::create([
                    'business_id' => $business->id,
                    'contact_id' => $lead->id,
                    'actor_id' => $request->user()->id,
                    'type' => 'note',
                    'description' => $data['note'],
                ]);
            }

            return $diff;
        }, 3);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'changes' => $changed]);
        }

        return back()->with('success', 'Lead updated.');
    }
}
