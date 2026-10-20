<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\BusinessOwnershipTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrowyaBusinessOwnershipController extends Controller
{
    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('super_admin', 'web'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeSuperAdmin();
        $data = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $search = trim($data['search'] ?? '');
        $businesses = Business::query()->with('owner:id,name,email')
            ->when($search !== '', fn($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('owner', fn($u) => $u->where('name', 'like', "%{$search}%"));
            }))->latest('id')->paginate(15)->withQueryString();
        return view('businesses.ownership.index', compact('businesses', 'search'));
    }

    public function edit(Business $business)
    {
        $this->authorizeSuperAdmin();
        $business->load('owner:id,name,email');
        $users = User::query()->where('status', 'active')
            ->whereKeyNot($business->owner_id)->orderBy('name')
            ->get(['id', 'name', 'email']);
        $history = BusinessOwnershipTransfer::query()->where('business_id', $business->id)
            ->with(['previousOwner:id,name,email', 'newOwner:id,name,email', 'performedBy:id,name'])
            ->latest('id')->limit(30)->get();
        return view('businesses.ownership.edit', compact('business', 'users', 'history'));
    }

    public function update(Request $request, Business $business)
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'new_owner_id' => ['required', 'integer', 'exists:users,id'],
            'expected_owner_id' => ['required', 'integer', 'min:1'],
            'confirmation' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if (trim($validated['confirmation']) !== $business->name) {
            throw ValidationException::withMessages([
                'confirmation' => 'Enter the exact business name to confirm this transfer.',
            ]);
        }

        DB::transaction(function () use ($business, $validated) {
            $locked = Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            if ((int) $locked->owner_id !== (int) $validated['expected_owner_id']) {
                throw ValidationException::withMessages([
                    'new_owner_id' => 'Owner has changed since this page opened. Refresh before transferring.',
                ]);
            }
            if ((int) $locked->owner_id === (int) $validated['new_owner_id']) {
                throw ValidationException::withMessages(['new_owner_id' => 'This user already owns the business.']);
            }
            $newOwner = User::query()->whereKey($validated['new_owner_id'])->lockForUpdate()->first();
            if (!$newOwner || $newOwner->status !== 'active') {
                throw ValidationException::withMessages(['new_owner_id' => 'Choose an active user.']);
            }
            $oldOwnerId = $locked->owner_id;

            // Unique (business_id, user_id) index prevents duplicate memberships.
            BusinessUser::query()->updateOrCreate(
                ['business_id' => $locked->id, 'user_id' => $newOwner->id],
                ['role' => 'owner', 'is_active' => true, 'joined_at' => now()]
            );
            $locked->update(['owner_id' => $newOwner->id]);

            // Revoke former owner's membership for THIS business only.
            // Do not change their global Spatie roles or other business memberships.
            if ($oldOwnerId) {
                $oldMembership = BusinessUser::query()
                    ->where('business_id', $locked->id)->where('user_id', $oldOwnerId)->first();
                if ($oldMembership) {
                    $oldMembership->update(['role' => 'staff', 'is_active' => false]);
                }
            }

            BusinessOwnershipTransfer::create([
                'business_id' => $locked->id,
                'previous_owner_id' => $oldOwnerId,
                'new_owner_id' => $newOwner->id,
                'performed_by' => auth()->id(),
                'reason' => trim($validated['reason']),
            ]);
        }, 3);

        return redirect()->route('growya.ownership.edit', $business)
            ->with('success', 'Ownership transferred successfully.');
    }
}
