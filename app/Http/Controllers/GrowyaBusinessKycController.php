<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaKycDocument;
use App\Models\GrowyaKycEvent;
use App\Models\GrowyaKycSubmission;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaBusinessKycController extends Controller
{
    private function adminOnly(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
    }

    private function ownerOnly(Business $business): void
    {
        // Business ka status (active/inactive) yahan check nahi hota: owner ko KYC karne dena chahiye
        // chahe listing abhi active na ho. Sirf active user + owner (ya super admin) allowed hai.
        abort_unless(
            auth()->check()
                && auth()->user()->status === 'active'
                && ((int) $business->owner_id === (int) auth()->id() || auth()->user()->hasRole('super_admin', 'web') || app(BusinessAccessService::class)->can(auth()->user(), $business, request()->isMethodSafe() ? 'documents.view' : 'documents.manage')),
            403
        );
    }

    private function visible(GrowyaKycSubmission $submission): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web') || (int) $submission->business->owner_id === (int) auth()->id() || app(BusinessAccessService::class)->can(auth()->user(), $submission->business, 'documents.view'), 403);
    }

    public function owner(Business $business)
    {
        $this->ownerOnly($business);
        $submissions = GrowyaKycSubmission::with(['documents', 'reviewer:id,name'])->where('business_id', $business->id)->latest('id')->get();

        return view('growya-kyc.owner', compact('business', 'submissions'));
    }

    public function submit(Request $request, Business $business, GrowyaNotificationService $notify)
    {
        $this->ownerOnly($business);
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', Rule::in(['proprietorship', 'partnership', 'llp', 'private_limited', 'other'])],
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'expires_on' => ['nullable', 'array'],
            'expires_on.*' => ['nullable', 'date', 'after:today'],
            'declaration' => ['accepted'],
        ]);
        $allowed = ['gst_certificate', 'pan', 'business_registration', 'owner_identity', 'address_proof'];
        $uploads = $request->file('documents', []);
        foreach ($uploads as $type => $file) {
            if (! in_array($type, $allowed, true)) {
                throw ValidationException::withMessages(['documents' => 'Unsupported document type.']);
            }
        }
        // Declaration ka proof: kisne, kab aur kis IP se accept kiya (extra column ki zaroorat nahi).
        $declaration = 'Owner confirmed document authenticity | user #'.auth()->id().' | '.now()->toIso8601String().' | IP '.$request->ip();

        $paths = [];
        try {
            // Hold the business row so simultaneous submissions cannot create two pending reviews.
            $submission = DB::transaction(function () use ($business, $data, $uploads, $declaration, &$paths) {
                Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                if (GrowyaKycSubmission::where('business_id', $business->id)->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages(['documents' => 'A KYC request is already pending review.']);
                }
                $previous = GrowyaKycSubmission::with('documents')->where('business_id', $business->id)->latest('id')->first();

                $submission = GrowyaKycSubmission::create([
                    'business_id' => $business->id,
                    'submitted_by' => auth()->id(),
                    'legal_name' => trim($data['legal_name']),
                    'entity_type' => $data['entity_type'],
                    'status' => 'pending',
                    'declaration' => $declaration,
                ]);
                foreach ($uploads as $type => $file) {
                    $path = $file->store('growya-kyc/'.$business->id.'/'.$submission->id, 'local');
                    if (! $path) {
                        throw new \RuntimeException('Private document storage failed.');
                    }
                    $paths[] = $path;
                    GrowyaKycDocument::create([
                        'submission_id' => $submission->id,
                        'document_type' => $type,
                        'file_path' => $path,
                        'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'file_size' => $file->getSize(),
                        'expires_on' => data_get($data, 'expires_on.'.$type),
                    ]);
                }

                // Reverification: pichla KYC approved tha to jo documents naye upload nahi hue,
                // wo (expire na hue ho to) saath carry-over hote hain. Rejected submission ke documents
                // carry-over nahi hote, kyunki admin ne unhe reject kiya tha.
                $carried = 0;
                if ($previous && $previous->status === 'approved') {
                    foreach ($previous->documents as $old) {
                        if (isset($uploads[$old->document_type])) {
                            continue;
                        }
                        if ($old->expires_on && now()->gt($old->expires_on)) {
                            continue;
                        }
                        if (! Storage::disk('local')->exists($old->file_path)) {
                            continue;
                        }
                        GrowyaKycDocument::create([
                            'submission_id' => $submission->id,
                            'document_type' => $old->document_type,
                            'file_path' => $old->file_path, // same private file; $paths me nahi, to rollback par delete nahi hogi
                            'original_name' => $old->original_name,
                            'mime_type' => $old->mime_type,
                            'file_size' => $old->file_size,
                            'expires_on' => $old->expires_on,
                        ]);
                        $carried++;
                    }
                }

                GrowyaKycEvent::create([
                    'submission_id' => $submission->id,
                    'actor_id' => auth()->id(),
                    'action' => 'submitted',
                    'reason' => $carried > 0 ? $carried.' previously approved document(s) carried over.' : null,
                ]);

                return $submission;
            }, 3);
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
        $notify->send($notify->superAdmins(), 'business_kyc_submitted', 'New KYC submission', $business->name.' submitted KYC documents for review.', route('growya.kyc.admin.show', $submission, false), $business->id);

        return back()->with('success', 'KYC submitted securely for review.');
    }

    public function admin(Request $request)
    {
        $this->adminOnly();
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])]])['status'] ?? 'pending';
        $query = GrowyaKycSubmission::with(['business:id,name,owner_id', 'submitter:id,name'])->latest('id');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $submissions = $query->paginate(20)->withQueryString();

        return view('growya-kyc.admin', compact('submissions', 'status'));
    }

    public function adminShow(GrowyaKycSubmission $submission)
    {
        $this->adminOnly();
        $submission->load(['business', 'documents', 'submitter:id,name', 'reviewer:id,name', 'events.actor:id,name']);

        return view('growya-kyc.review', compact('submission'));
    }

    public function review(Request $request, GrowyaKycSubmission $submission, GrowyaNotificationService $notify)
    {
        $this->adminOnly();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'min:5', 'max:2000'],
            'reverify_by' => ['nullable', 'date', 'after:today'],
        ]);
        DB::transaction(function () use ($submission, $data) {
            $locked = GrowyaKycSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This submission is already reviewed.']);
            }
            if (! $locked->documents()->exists()) {
                throw ValidationException::withMessages(['decision' => 'No documents uploaded.']);
            }
            $locked->update([
                'status' => $data['decision'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_reason' => $data['reason'] ?? null,
                'reverify_by' => $data['decision'] === 'approved' ? ($data['reverify_by'] ?? null) : null,
            ]);
            GrowyaKycEvent::create(['submission_id' => $locked->id, 'actor_id' => auth()->id(), 'action' => $data['decision'], 'reason' => $data['reason'] ?? null]);
        }, 3);

        $business = $submission->business;
        $message = 'KYC review for '.$business->name.': '.$data['decision'].'.';
        if ($data['decision'] === 'rejected' && ! empty($data['reason'])) {
            // Owner ko wajah notification mein hi dikh jaye.
            $message .= ' Reason: '.Str::limit($data['reason'], 200);
        }
        $notify->send([(int) $business->owner_id], 'business_kyc_reviewed', 'Business KYC '.ucfirst($data['decision']), $message, route('growya.kyc.owner', $business, false), $submission->business_id);

        return back()->with('success', 'Review saved.');
    }

    public function download(GrowyaKycSubmission $submission, GrowyaKycDocument $document)
    {
        $this->visible($submission);
        abort_unless((int) $document->submission_id === (int) $submission->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
