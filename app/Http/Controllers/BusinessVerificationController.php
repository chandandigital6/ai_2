<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessVerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessVerificationController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            auth()->check()
            && auth()->user()->hasRole('super_admin'),
            403,
            'Only Super Admin can manage business approvals.'
        );
    }

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'approved',
                    'rejected',
                    'all',
                ]),
            ],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $filter = $request->input('status', 'pending');

        $query = Business::query()
            ->with(['owner:id,name']);

        if ($filter !== 'all') {
            $query->where('verification_status', $filter);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $businesses = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = Business::query()
            ->selectRaw('verification_status, COUNT(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        $total = Business::query()->count();

        return view('businesses.verification.index', compact(
            'businesses',
            'counts',
            'total',
            'filter'
        ));
    }

    public function show(Business $business)
    {
        // dd($business->verification_status);
        $this->authorizeSuperAdmin();

        $business->load([
            'owner:id,name',
            'verificationLogs' => fn ($q) => $q
                ->with('performedBy:id,name')
                ->latest('id'),
        ]);
      
        

        return view('businesses.verification.show', compact(
            'business'
        ));
    }

    public function approve(Business $business)
    {
        $this->authorizeSuperAdmin();

        DB::transaction(function () use ($business) {
            $locked = Business::query()
                ->whereKey($business->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->verification_status === 'approved') {
                return;
            }

            $oldStatus = $locked->verification_status;

            $locked->update([
                'verification_status' => 'approved',
            ]);

            BusinessVerificationLog::create([
                'business_id' => $locked->id,
                'performed_by' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'approved',
                'reason' => null,
            ]);
        }, 3);

        return back()->with(
            'success',
            'Business approved successfully.'
        );
    }

    public function reject(
        Request $request,
        Business $business
    ) {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($business, $data) {
            $locked = Business::query()
                ->whereKey($business->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $locked->verification_status;

            $locked->update([
                'verification_status' => 'rejected',
            ]);

            BusinessVerificationLog::create([
                'business_id' => $locked->id,
                'performed_by' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'reason' => trim($data['reason']),
            ]);
        }, 3);

        return back()->with(
            'success',
            'Business rejected successfully.'
        );
    }

    public function pending(Business $business)
    {
        $this->authorizeSuperAdmin();

        DB::transaction(function () use ($business) {
            $locked = Business::query()
                ->whereKey($business->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->verification_status === 'pending') {
                return;
            }

            $oldStatus = $locked->verification_status;

            $locked->update([
                'verification_status' => 'pending',
            ]);

            BusinessVerificationLog::create([
                'business_id' => $locked->id,
                'performed_by' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'pending',
                'reason' => 'Sent for re-verification by Super Admin.',
            ]);
        }, 3);

        return back()->with(
            'success',
            'Business moved to pending verification.'
        );
    }
}
