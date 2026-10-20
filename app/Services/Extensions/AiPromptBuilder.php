<?php

namespace App\Services\Extensions;

use App\Models\AiBrandAsset;
use App\Models\AiBrandProfile;
use App\Models\AiPromptPreset;
use App\Models\Business;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiPromptBuilder
{
    public function build(Business $business, array $d, array $plan): array
    {
        $preset = empty($d['preset_id']) ? null : AiPromptPreset::where('business_id', $business->id)->findOrFail($d['preset_id']);
        if ($preset && $preset->generation_type !== $d['generation_type']) {
            throw ValidationException::withMessages(['preset_id' => 'Choose a saved prompt for the selected generation type.']);
        }
        $profile = AiBrandProfile::where('business_id', $business->id)->first();
        $refs = array_key_exists('reference_ids', $d) ? ($d['reference_ids'] ?? []) : ($preset?->reference_ids ?? []);
        // Without an explicit preset/reference selection, include the saved business reference library.
        if (! $preset && ! array_key_exists('reference_ids', $d)) {
            $refs = AiBrandAsset::where('business_id', $business->id)->where('kind', 'reference')->pluck('id')->all();
        }
        if (count($refs) > $plan['max_references']) {
            throw ValidationException::withMessages(['reference_ids' => 'This package allows only '.$plan['max_references'].' reference images. Select fewer references or a larger package.']);
        }
        $assets = AiBrandAsset::where('business_id', $business->id)->whereIn('id', $refs)->where('kind', 'reference')->get();
        if ($assets->count() !== count($refs)) {
            throw ValidationException::withMessages(['reference_ids' => 'A selected reference was removed or belongs to another business.']);
        }
        $logo = AiBrandAsset::where('business_id', $business->id)->where('kind', 'logo')->latest('id')->first();
        if ($logo) {
            $assets->prepend($logo);
        }
        $totalBytes = 0;
        foreach ($assets as $asset) {
            if (! Storage::disk('local')->exists($asset->path)) {
                throw ValidationException::withMessages(['reference_ids' => 'A saved image is missing. Remove it and upload it again.']);
            }
            $totalBytes += Storage::disk('local')->size($asset->path);
        }
        if ($totalBytes > 20 * 1024 * 1024) {
            throw ValidationException::withMessages(['reference_ids' => 'Selected images including the logo must total 20 MB or less. Use smaller references.']);
        }
        $size = $d['size'] ?? $preset?->size ?? $profile?->default_size ?? '1024x1024';
        $language = $d['language'] ?? $preset?->language ?? $profile?->language ?? 'Hindi';
        $parts = ['Create a '.$d['generation_type'].' creative for '.$business->name.'.', 'Output language: '.$language.'.'];
        if ($profile) {
            foreach (['brand_name', 'tagline', 'contact', 'address', 'colors', 'instructions'] as $field) {
                if ($profile->$field) {
                    $parts[] = ucfirst(str_replace('_', ' ', $field)).': '.$profile->$field;
                }
            }
        }
        if ($preset) {
            $parts[] = 'Saved creative brief: '.$preset->prompt;
        }
        if (! empty($d['prompt'])) {
            $parts[] = 'Current request: '.$d['prompt'];
        }
        if ($preset?->negative_prompt) {
            $parts[] = 'Avoid: '.$preset->negative_prompt;
        }
        foreach ($assets as $i => $asset) {
            $parts[] = 'Reference image '.($i + 1).': '.$asset->kind.' — '.$asset->label;
        }
        if ($logo) {
            $parts[] = 'Use the provided logo faithfully. Preserve its spelling, shape and brand colors.';
        }
        $variation = (string) Str::uuid();
        if ($d['vary_design'] ?? true) {
            $parts[] = 'Create a fresh composition, camera angle and background while preserving the brand and referenced product. Creative variation: '.now()->toDateString().' / '.$variation;
        }

        return ['prompt' => implode("\n", $parts), 'size' => $size, 'language' => $language,
            'assets' => $assets->map(fn ($a) => ['id' => $a->id, 'path' => $a->path, 'mime' => $a->mime, 'kind' => $a->kind])->values()->all(),
            'preset_id' => $preset?->id, 'variation_key' => $variation];
    }
}
