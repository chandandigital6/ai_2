<?php

namespace App\Http\Controllers\Extensions;

use App\Jobs\Extensions\GenerateAi;
use App\Models\AiCreditTransaction;
use App\Models\AiGeneration;
use App\Models\AiMedia;
use App\Models\AiPlanRequest;
use App\Models\Business;
use App\Services\Extensions\AiPlanService;
use App\Services\Extensions\AiPromptBuilder;
use App\Services\Extensions\AiWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiController extends ExtensionController
{
    public function index(Request $r, Business $business, AiWalletService $wallet)
    {
        $this->business($r, $business, 'ai.view');

        return response()->json(['data' => ['wallet' => $wallet->balance($business->id), 'entitlements' => app(AiPlanService::class)->entitlements($business->id), 'generations' => AiGeneration::where('business_id', $business->id)->latest('id')->paginate(20)->through(fn ($g) => $this->generationData($g))]]);
    }

    public function store(Request $r, Business $business)
    {
        $this->business($r, $business, 'ai.use');
        abort_unless(config('growya_extensions.ai.enabled') && config('growya_extensions.ai.key'), 503, 'AI provider is not configured. Contact the administrator.');
        $d = $r->validate([
            'generation_type' => ['required', Rule::in(['text', 'image'])],
            'prompt' => ['nullable', 'required_without:preset_id', 'string', 'min:5', 'max:8000'],
            'preset_id' => ['nullable', 'integer'], 'plan_request_id' => ['nullable', 'integer'],
            'request_key' => ['required', 'string', 'max:100'],
            'size' => ['nullable', Rule::in(config('growya_ai.sizes'))], 'language' => ['nullable', 'string', 'max:60'],
            'vary_design' => ['sometimes', 'boolean'],
            'reference_ids' => ['sometimes', 'nullable', 'array', 'max:10'], 'reference_ids.*' => ['integer', 'distinct'],
            'model' => ['prohibited'], 'credits_used' => ['prohibited'], 'business_id' => ['prohibited'],
        ]);
        $fingerprint = hash('sha256', json_encode(Arr::sortRecursive(Arr::except($d, ['request_key']))));
        $g = DB::transaction(function () use ($r, $business, $d, $fingerprint) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $old = AiGeneration::where('business_id', $business->id)->where('request_key', $d['request_key'])->first();
            if ($old) {
                if (($old->input_json['request_fingerprint'] ?? null) !== $fingerprint) {
                    throw ValidationException::withMessages(['request_key' => 'This request key was already used for a different generation.']);
                }

                return $old;
            }
            if (AiGeneration::where('business_id', $business->id)->whereIn('status', ['queued', 'processing'])->count() >= config('growya_ai.max_in_flight')) {
                throw ValidationException::withMessages(['generation' => 'Two generations are already running. Wait for one to finish.']);
            }
            $pack = app(AiPlanService::class)->pack($business->id, $d['plan_request_id'] ?? null, $d['generation_type']);
            $pack = AiPlanRequest::whereKey($pack->id)->lockForUpdate()->firstOrFail();
            $plan = $pack->plan_snapshot;
            $input = app(AiPromptBuilder::class)->build($business, $d, $plan);
            $input['request_fingerprint'] = $fingerprint;
            $credits = (int) $plan[$d['generation_type'].'_credits'];
            $g = AiGeneration::create(['business_id' => $business->id, 'user_id' => $r->user()->id,
                'ai_plan_request_id' => $pack->id, 'generation_type' => $d['generation_type'], 'provider' => 'openai',
                'model' => $plan[$d['generation_type'].'_model'], 'prompt' => $input['prompt'], 'input_json' => $input,
                'language' => $input['language'], 'credits_used' => $credits, 'status' => 'queued']);
            $g->forceFill(['request_key' => $d['request_key']])->save();
            $pack->increment('used_credits', $credits);
            app(AiWalletService::class)->change($business->id, $credits, 'debit', 'generation:'.$g->id, $g->id);
            GenerateAi::dispatch($g->id)->afterCommit();

            return $g;
        }, 3);

        return $this->reply($r, $this->generationData($g), 202);
    }

    private function generationData(AiGeneration $g): array
    {
        $data = $g->toArray();
        unset($data['input_json'], $data['request_key'], $data['ai_media']);
        $data['media'] = $g->ai_media->map(fn ($m) => ['id' => $m->id, 'media_type' => $m->media_type,
            'download_path' => 'businesses/'.$g->business_id.'/ai/media/'.$m->id]);

        return $data;
    }

    public function show(Request $r, Business $business, AiGeneration $generation)
    {
        $this->business($r, $business, 'ai.view');
        abort_unless((int) $generation->business_id === (int) $business->id, 404);

        return response()->json(['data' => $this->generationData($generation)]);
    }

    public function media(Request $r, Business $business, AiMedia $media)
    {
        $this->business($r, $business, 'ai.view');
        abort_unless((int) $media->business_id === (int) $business->id, 404);
        abort_unless(Storage::disk('local')->exists($media->file_url), 404);

        return $r->boolean('inline')
            ? Storage::disk('local')->response($media->file_url, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])
            : Storage::disk('local')->download($media->file_url);
    }

    public function credits(Request $r, Business $business, AiWalletService $wallet)
    {
        $this->business($r, $business, 'ai.view');

        return response()->json(['data' => ['wallet' => $wallet->balance($business->id), 'entitlements' => app(AiPlanService::class)->entitlements($business->id), 'transactions' => AiCreditTransaction::where('business_id', $business->id)->latest('id')->paginate(30)]]);
    }

    public function grant(Request $r, Business $business, AiWalletService $wallet)
    {
        $this->admin($r, 'ai.credits.manage');
        $d = $r->validate(['credits' => ['required', 'integer', 'min:1', 'max:1000000'], 'request_key' => ['required', 'string', 'max:100']]);
        $wallet->change($business->id, $d['credits'], 'grant', 'admin:'.hash('sha256', $d['request_key']));

        return $this->reply($r, $wallet->balance($business->id));
    }
}
