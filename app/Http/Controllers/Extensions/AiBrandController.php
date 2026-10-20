<?php

namespace App\Http\Controllers\Extensions;

use App\Models\AiBrandAsset;
use App\Models\AiBrandProfile;
use App\Models\AiPromptPreset;
use App\Models\Business;
use App\Services\Extensions\AiPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiBrandController extends ExtensionController
{
    public function index(Request $r, Business $business, AiPlanService $plans)
    {
        $this->business($r, $business, 'ai.view');

        return response()->json(['data' => ['profile' => AiBrandProfile::where('business_id', $business->id)->first(),
            'assets' => AiBrandAsset::where('business_id', $business->id)->get(),
            'presets' => AiPromptPreset::where('business_id', $business->id)->orderBy('name')->get(),
            'entitlements' => $plans->entitlements($business->id)]]);
    }

    public function profile(Request $r, Business $business)
    {
        $this->business($r, $business, 'ai.settings.manage');
        $d = $r->validate(['brand_name' => ['nullable', 'string', 'max:255'], 'tagline' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:2000'],
            'colors' => ['nullable', 'string', 'max:255'], 'language' => ['required', 'string', 'max:60'],
            'default_size' => ['required', Rule::in(config('growya_ai.sizes'))], 'instructions' => ['nullable', 'string', 'max:5000']]);
        $profile = DB::transaction(function () use ($business, $d) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            return AiBrandProfile::updateOrCreate(['business_id' => $business->id], $d);
        });

        return $this->reply($r, $profile->toArray());
    }

    public function upload(Request $r, Business $business, AiPlanService $plans)
    {
        $this->business($r, $business, 'ai.settings.manage');
        $d = $r->validate(['kind' => ['required', Rule::in(['logo', 'reference'])],
            'label' => ['required', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:'.config('growya_ai.max_asset_kb'), 'dimensions:max_width=8192,max_height=8192']]);
        $path = null;
        try {
            $asset = DB::transaction(function () use ($r, $business, $plans, $d, &$path) {
                Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                if ($d['kind'] === 'reference') {
                    $max = $plans->entitlements($business->id)['max_references'];
                    if (AiBrandAsset::where('business_id', $business->id)->where('kind', 'reference')->count() >= $max) {
                        throw ValidationException::withMessages(['image' => 'Reference image limit reached. An approved package with more references is required.']);
                    }
                }
                $path = $r->file('image')->store('ai-brand/'.$business->id, 'local');
                if (! $path) {
                    throw new \RuntimeException('Image could not be saved.');
                }
                if ($d['kind'] === 'logo') {
                    AiBrandAsset::where('business_id', $business->id)->where('kind', 'logo')->delete();
                }

                return AiBrandAsset::create(['business_id' => $business->id, 'kind' => $d['kind'], 'label' => $d['label'],
                    'path' => $path, 'mime' => $r->file('image')->getMimeType()]);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return $this->reply($r, $asset->toArray(), 201);
    }

    public function asset(Request $r, Business $business, AiBrandAsset $asset)
    {
        $this->business($r, $business, 'ai.view');
        abort_unless((int) $asset->business_id === (int) $business->id, 404);
        abort_unless(Storage::disk('local')->exists($asset->path), 404);

        return Storage::disk('local')->response($asset->path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function deleteAsset(Request $r, Business $business, AiBrandAsset $asset)
    {
        $this->business($r, $business, 'ai.settings.manage');
        abort_unless((int) $asset->business_id === (int) $business->id, 404);
        // Keep immutable bytes for already queued generations; remove from future selectable assets.
        $asset->delete();

        return $this->reply($r, ['deleted' => true]);
    }

    private function presetData(Request $r, Business $business, AiPlanService $plans): array
    {
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'generation_type' => ['required', Rule::in(['text', 'image'])],
            'prompt' => ['required', 'string', 'min:5', 'max:8000'], 'negative_prompt' => ['nullable', 'string', 'max:2000'],
            'size' => ['required', Rule::in(config('growya_ai.sizes'))], 'language' => ['required', 'string', 'max:60'],
            'reference_ids' => ['nullable', 'array', 'max:10'], 'reference_ids.*' => ['integer', 'distinct',
                Rule::exists('ai_brand_assets', 'id')->where(fn ($q) => $q->where('business_id', $business->id)->where('kind', 'reference')->whereNull('deleted_at'))]]);
        if (count($d['reference_ids'] ?? []) > $plans->entitlements($business->id)['max_references']) {
            throw ValidationException::withMessages(['reference_ids' => 'Selected references exceed your active package allowance.']);
        }

        return $d;
    }

    public function storePreset(Request $r, Business $business, AiPlanService $plans)
    {
        $this->business($r, $business, 'ai.settings.manage');
        $preset = DB::transaction(function () use ($r, $business, $plans) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $d = $this->presetData($r, $business, $plans);
            if (AiPromptPreset::where('business_id', $business->id)->count() >= $plans->entitlements($business->id)['max_prompts']) {
                throw ValidationException::withMessages(['name' => 'Saved prompt limit reached. Upgrade your AI package or delete an old prompt.']);
            }

            return AiPromptPreset::create($d + ['business_id' => $business->id]);
        }, 3);

        return $this->reply($r, $preset->toArray(), 201);
    }

    public function updatePreset(Request $r, Business $business, AiPromptPreset $preset, AiPlanService $plans)
    {
        $this->business($r, $business, 'ai.settings.manage');
        abort_unless((int) $preset->business_id === (int) $business->id, 404);
        $preset->update($this->presetData($r, $business, $plans));

        return $this->reply($r, $preset->fresh()->toArray());
    }

    public function deletePreset(Request $r, Business $business, AiPromptPreset $preset)
    {
        $this->business($r, $business, 'ai.settings.manage');
        abort_unless((int) $preset->business_id === (int) $business->id, 404);
        $preset->delete();

        return $this->reply($r, ['deleted' => true]);
    }
}
