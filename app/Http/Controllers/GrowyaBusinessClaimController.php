<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\BusinessOwnershipTransfer;
use App\Models\GrowyaBusinessClaim;
use App\Models\GrowyaBusinessClaimDocument;
use App\Models\GrowyaBusinessClaimEvent;
use App\Models\User;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaBusinessClaimController extends Controller
{
    private function adminOnly(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
    }
    private function applicantOnly(): void
    {
        abort_unless(auth()->check() && auth()->user()->status === 'active', 403);
    }
    private function canView(GrowyaBusinessClaim $claim): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web') || (int)$claim->claimant_id === (int)auth()->id(), 403);
    }
    public function create(Business $business)
    {
        $this->applicantOnly();
        abort_if((int)$business->owner_id === (int)auth()->id(), 422, 'You already own this business.');
        abort_if($business->status !== 'active', 404);
        $latest = GrowyaBusinessClaim::where('business_id', $business->id)->where('claimant_id', auth()->id())->latest('id')->first();
        return view('growya-claims.create', compact('business', 'latest'));
    }
    public function store(Request $request, Business $business, GrowyaNotificationService $notify)
    {
        $this->applicantOnly();
        $data = $request->validate([
            'claimed_role' => ['required', Rule::in(['proprietor', 'partner', 'director', 'authorized_representative'])],
            'reason' => ['required', 'string', 'min:20', 'max:3000'],
            'document_type' => ['required', Rule::in(['gst', 'business_registration', 'authorization_letter', 'other'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'declaration' => ['accepted'],
        ]);
        $path = null;
        try {
            $claim = DB::transaction(function () use ($business, $data, $request, &$path) {
                $locked = Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'active' || (int)$locked->owner_id === (int)auth()->id()) {
                    throw ValidationException::withMessages(['reason' => 'Business is unavailable or already yours.']);
                }
                if (GrowyaBusinessClaim::where('business_id', $locked->id)->where('claimant_id', auth()->id())->whereIn('status', ['pending', 'under_review'])->exists()) {
                    throw ValidationException::withMessages(['reason' => 'You already have a pending claim for this business.']);
                }
                $claim = GrowyaBusinessClaim::create([
                    'business_id' => $locked->id,
                    'claimant_id' => auth()->id(),
                    'owner_id_at_submission' => $locked->owner_id,
                    'status' => 'pending',
                    'claimed_role' => $data['claimed_role'],
                    'reason' => trim($data['reason']),
                ]);
                $path = $request->file('document')->store('growya-business-claims/' . $locked->id . '/' . $claim->id, 'local');
                if (!$path) throw new \RuntimeException('Private document upload failed.');
                GrowyaBusinessClaimDocument::create([
                    'claim_id' => $claim->id,
                    'document_type' => $data['document_type'],
                    'file_path' => $path,
                    'original_name' => mb_substr(basename($request->file('document')->getClientOriginalName()), 0, 255),
                    'mime_type' => $request->file('document')->getMimeType(),
                    'file_size' => $request->file('document')->getSize(),
                ]);
                GrowyaBusinessClaimEvent::create(['claim_id' => $claim->id, 'actor_id' => auth()->id(), 'action' => 'submitted']);
                return $claim;
            }, 3);
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        $ids = $notify->superAdmins();
        $owner = (int)$business->owner_id;
        if ($owner && $owner !== (int)auth()->id()) $ids[] = $owner;
        $notify->send(
            $ids,
            'business_claim_submitted',
            'Business ownership claim',
            'A new ownership claim was submitted for ' . $business->name . '.',
            null,
            $business->id
        );
        return redirect()->route('growya.claims.mine')->with('success', 'Claim submitted. You will not gain business access until admin approval.');
    }
    public function mine()
    {
        $this->applicantOnly();
        $claims = GrowyaBusinessClaim::with('business:id,name,slug')->where('claimant_id', auth()->id())->latest('id')->paginate(15);
        return view('growya-claims.mine', compact('claims'));
    }
    public function admin(Request $request)
    {
        $this->adminOnly();
        $data = $request->validate(['status' => ['nullable', Rule::in(['pending', 'under_review', 'approved', 'rejected', 'all'])]]);
        $status = $data['status'] ?? 'pending';
        $claims = GrowyaBusinessClaim::with(['business:id,name,owner_id', 'claimant:id,name,email'])->when($status !== 'all', fn($q) => $q->where('status', $status))->latest('id')->paginate(20)->withQueryString();
        return view('growya-claims.admin', compact('claims', 'status'));
    }
    public function show(GrowyaBusinessClaim $claim)
    {
        $this->canView($claim);
          $claim->load(['business', 'claimant:id,name,email', 'documents', 'reviewer:id,name']);
   if (auth()->user()->hasRole('super_admin', 'web')) {
       $claim->load(['business.owner:id,name,email', 'events.actor:id,name']);
   }
        // $claim->load(['business.owner:id,name,email', 'claimant:id,name,email', 'documents', 'events.actor:id,name', 'reviewer:id,name']);
        return view('growya-claims.show', compact('claim'));
    }
    public function document(GrowyaBusinessClaim $claim, GrowyaBusinessClaimDocument $document)
    {
        $this->canView($claim);
        abort_unless((int)$document->claim_id === (int)$claim->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);
        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }
    public function review(Request $request, GrowyaBusinessClaim $claim, GrowyaNotificationService $notify)
    {
        $this->adminOnly();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['under_review', 'approved', 'rejected'])],
            'admin_note' => ['required', 'string', 'min:10', 'max:2000'],
            'expected_owner_id' => ['required', 'integer', 'min:1'],
            'confirmation' => ['nullable', 'string'],
        ]);
        $decision = $data['decision'];
        DB::transaction(function () use ($claim, $data, $decision) {
            $lockedClaim = GrowyaBusinessClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if (!in_array($lockedClaim->status, ['pending', 'under_review'], true)) {
                throw ValidationException::withMessages(['decision' => 'This claim was already decided.']);
            }
            $business = Business::whereKey($lockedClaim->business_id)->lockForUpdate()->firstOrFail();
            if ($decision === 'approved') {
                if ($business->status !== 'active' || $business->verification_status !== 'approved') {
                    throw ValidationException::withMessages(['decision' => 'Business must be active and approved for transfer.']);
                }
                if ((int)$business->owner_id !== (int)$data['expected_owner_id'] || (int)$business->owner_id !== (int)$lockedClaim->owner_id_at_submission) {
                    throw ValidationException::withMessages(['decision' => 'Owner has changed. Reverify identity and ask for a fresh claim.']);
                }
                if (trim($data['confirmation'] ?? '') !== $business->name) {
                    throw ValidationException::withMessages(['confirmation' => 'Enter the exact business name to authorize transfer.']);
                }
                $newOwner = User::whereKey($lockedClaim->claimant_id)->lockForUpdate()->first();
                if (!$newOwner || $newOwner->status !== 'active' || (int)$newOwner->id === (int)$business->owner_id) {
                    throw ValidationException::withMessages(['decision' => 'Claimant is not eligible for ownership transfer.']);
                }
                $previousOwnerId = $business->owner_id;
                BusinessUser::updateOrCreate(
                    ['business_id' => $business->id, 'user_id' => $newOwner->id],
                    ['role' => 'owner', 'is_active' => true, 'joined_at' => now()]
                );
                $business->update(['owner_id' => $newOwner->id]);
                if ($previousOwnerId) {
                    BusinessUser::where('business_id', $business->id)->where('user_id', $previousOwnerId)
                        ->update(['role' => 'staff', 'is_active' => false]);
                }
                BusinessOwnershipTransfer::create([
                    'business_id' => $business->id,
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => $newOwner->id,
                    'performed_by' => auth()->id(),
                    'reason' => 'Approved ownership claim #' . $lockedClaim->id . ': ' . $data['admin_note'],
                ]);
                GrowyaBusinessClaim::where('business_id', $business->id)->where('id', '!=', $lockedClaim->id)
                    ->whereIn('status', ['pending', 'under_review'])
                    ->update([
                        'status' => 'rejected',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                        'admin_note' => 'Closed because a different claim was approved.'
                    ]);
            }
            $lockedClaim->update([
                'status' => $decision,
                'admin_note' => trim($data['admin_note']),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => $decision === 'under_review' ? null : now()
            ]);
            GrowyaBusinessClaimEvent::create([
                'claim_id' => $lockedClaim->id,
                'actor_id' => auth()->id(),
                'action' => $decision,
                'notes' => trim($data['admin_note'])
            ]);
        }, 3);
        $ids = [(int)$claim->claimant_id];
        if ((int)$claim->owner_id_at_submission > 0 && (int)$claim->owner_id_at_submission !== (int)$claim->claimant_id) {
            $ids[] = (int)$claim->owner_id_at_submission;
        }
        $notify->send(
            [(int)$claim->claimant_id],
            'business_claim_' . $decision,
            'Business ownership claim updated',
            'Claim #' . $claim->id . ' status: ' . $decision . '.',
            route('growya.claims.show', $claim, false),
            $claim->business_id
        );
        if ((int)$claim->owner_id_at_submission > 0 && (int)$claim->owner_id_at_submission !== (int)$claim->claimant_id) {
            $notify->send(
                [(int)$claim->owner_id_at_submission],
                'business_claim_' . $decision,
                'Business ownership claim updated',
                'Claim #' . $claim->id . ' status: ' . $decision . '.',
                null,
                $claim->business_id
            );
        }
        return back()->with('success', 'Claim updated: ' . $decision);
    }
}
