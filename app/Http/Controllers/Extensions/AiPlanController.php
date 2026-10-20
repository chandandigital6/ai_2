<?php

namespace App\Http\Controllers\Extensions;

use App\Models\AiPlan;
use App\Models\AiPlanRequest;
use App\Models\Business;
use App\Services\Extensions\AiPlanService;
use App\Services\Extensions\AiWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiPlanController extends ExtensionController
{
    private function superAdmin(Request $r): void
    {
        abort_unless($r->user()?->status === 'active' && $r->user()->hasRole('super_admin', 'web'), 403, 'Only the super admin may manage AI packages and approvals.');
    }

    public function catalog(Request $r, Business $business, AiPlanService $service)
    {
        $this->business($r, $business, 'ai.view');

        return response()->json(['data' => ['plans' => AiPlan::where('is_active', true)->orderBy('price_paise')->get(),
            'entitlements' => $service->entitlements($business->id),
            'requests' => AiPlanRequest::where('business_id', $business->id)->latest('id')->paginate(20)]]);
    }

    public function requestPlan(Request $r, Business $business)
    {
        $this->business($r, $business, 'ai.view');
        abort_unless((int) $business->owner_id === (int) $r->user()->id, 403, 'Only the business owner can request a package.');
        $d = $r->validate(['ai_plan_id' => ['required', 'integer'], 'request_key' => ['required', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:255'], 'note' => ['nullable', 'string', 'max:2000']]);
        $purchase = DB::transaction(function () use ($r, $business, $d) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $old = AiPlanRequest::where('business_id', $business->id)->where('request_key', $d['request_key'])->first();
            if ($old) {
                if ((int) $old->ai_plan_id !== (int) $d['ai_plan_id'] || $old->payment_reference !== ($d['payment_reference'] ?? null) || $old->note !== ($d['note'] ?? null)) {
                    throw ValidationException::withMessages(['request_key' => 'This key was already used for a different package request.']);
                }

                return $old;
            }
            if (AiPlanRequest::where('business_id', $business->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['ai_plan_id' => 'A package request is already pending. Wait for the super admin to review it.']);
            }
            $plan = AiPlan::whereKey($d['ai_plan_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();

            return AiPlanRequest::create(['business_id' => $business->id, 'ai_plan_id' => $plan->id,
                'requested_by' => $r->user()->id, 'request_key' => $d['request_key'], 'plan_snapshot' => $plan->only([
                    'name', 'description', 'price_paise', 'currency', 'credits', 'valid_days', 'text_model', 'image_model',
                    'text_credits', 'image_credits', 'max_prompts', 'max_references']),
                'status' => 'pending', 'payment_reference' => $d['payment_reference'] ?? null, 'note' => $d['note'] ?? null]);
        }, 3);

        return $this->reply($r, $purchase->toArray(), 201);
    }

    public function adminIndex(Request $r)
    {
        $this->superAdmin($r);
        $data = ['plans' => AiPlan::orderBy('id')->get(),
            'requests' => AiPlanRequest::with('business')->when($r->filled('status'), fn ($q) => $q->where('status', $r->input('status')))->latest('id')->paginate(25)->withQueryString(),
            'editing' => $r->filled('edit') ? AiPlan::findOrFail($r->integer('edit')) : null];

        return $r->is('api/*') || $r->expectsJson() ? response()->json(['data' => $data]) : view('extensions.ai-admin', $data);
    }

    private function planData(Request $r): array
    {
        if ($r->has('price_rupees') && ! $r->has('price_paise')) {
            $amount = $r->validate(['price_rupees' => ['required', 'string', 'regex:/^\\d{1,7}(\\.\\d{1,2})?$/']])['price_rupees'];
            [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
            $r->merge(['price_paise' => ((int) $whole * 100) + (int) str_pad($fraction, 2, '0')]);
        }
        $d = $r->validate([
            'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000'],
            'price_paise' => ['required', 'integer', 'min:0', 'max:100000000'],
            'credits' => ['required', 'integer', 'min:1', 'max:1000000'], 'valid_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'text_model' => ['nullable', Rule::in(array_keys(config('growya_ai.text_models')))],
            'image_model' => ['nullable', Rule::in(array_keys(config('growya_ai.image_models')))],
            'text_credits' => ['required', 'integer', 'min:1', 'max:1000000'], 'image_credits' => ['required', 'integer', 'min:1', 'max:1000000'],
            'max_prompts' => ['required', 'integer', 'min:0', 'max:200'], 'max_references' => ['required', 'integer', 'min:0', 'max:10'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (empty($d['text_model']) && empty($d['image_model'])) {
            throw ValidationException::withMessages(['text_model' => 'Select at least one text or image model.']);
        }
        foreach (['text', 'image'] as $type) {
            if (! empty($d[$type.'_model']) && $d[$type.'_credits'] > $d['credits']) {
                throw ValidationException::withMessages([$type.'_credits' => 'Generation cost cannot exceed package credits.']);
            }
        }

        return $d;
    }

    public function store(Request $r)
    {
        $this->superAdmin($r);

        return $this->reply($r, AiPlan::create($this->planData($r))->toArray(), 201);
    }

    public function update(Request $r, AiPlan $plan)
    {
        $this->superAdmin($r);
        $plan->update($this->planData($r));

        return $this->reply($r, $plan->fresh()->toArray());
    }

    public function review(Request $r, AiPlanRequest $purchase, AiWalletService $wallet)
    {
        $this->superAdmin($r);
        $d = $r->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000']]);
        $purchase = DB::transaction(function () use ($r, $purchase, $d, $wallet) {
            Business::whereKey($purchase->business_id)->lockForUpdate()->firstOrFail();
            $p = AiPlanRequest::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if ($p->status === $d['decision']) {
                return $p;
            }
            if ($p->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This request has already been reviewed.']);
            }
            if ($d['decision'] === 'approved') {
                $wallet->change($p->business_id, (int) $p->plan_snapshot['credits'], 'grant', 'package:'.$p->id);
                $p->approved_at = now();
                $p->expires_at = now()->addDays((int) $p->plan_snapshot['valid_days']);
            }
            $p->fill(['status' => $d['decision'], 'reviewed_by' => $r->user()->id, 'review_note' => $d['review_note'] ?? null])->save();

            return $p;
        }, 3);

        return $this->reply($r, $purchase->toArray());
    }
}
