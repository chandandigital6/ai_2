<?php

namespace App\Jobs\Extensions;

use App\Exceptions\AiProviderException;
use App\Models\AiGeneration;
use App\Models\AiMedia;
use App\Models\Business;
use App\Models\User;
use App\Services\BusinessAccessService;
use App\Services\Extensions\AiFailureService;
use App\Services\Extensions\AiProvider;
use App\Services\Extensions\AiWalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 210;

    public bool $failOnTimeout = true;

    public function __construct(public int $generationId)
    {
        $this->onQueue('ai');
    }

    public function handle(AiProvider $provider, AiWalletService $wallet): void
    {
        if (! AiGeneration::whereKey($this->generationId)->where('status', 'queued')->update(['status' => 'processing'])) {
            return;
        }
        $g = AiGeneration::findOrFail($this->generationId);
        $path = null;
        try {
            $user = User::find($g->user_id);
            $business = Business::find($g->business_id);
            if (! $user || ! $business || ! app(BusinessAccessService::class)->can($user, $business, 'ai.use')) {
                throw new AiProviderException('access_revoked', 'Your permission to use AI was removed. Contact the business owner.');
            }
            $output = $provider->generate($g->generation_type, $g->prompt, array_merge($g->input_json ?? [], ['model' => $g->model]));
            $width = $output['width'] ?? null;
            $height = $output['height'] ?? null;
            if (isset($output['image'])) {
                $path = 'ai/'.$business->id.'/'.Str::uuid().'.png';
                if (! Storage::disk('local')->put($path, $output['image'])) {
                    throw new \RuntimeException('AI output could not be saved.');
                }
                $output = ['image' => true];
            }
            DB::transaction(function () use ($g, $output, $path, $width, $height) {
                $locked = AiGeneration::whereKey($g->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'processing') {
                    throw new \RuntimeException('AI generation no longer processing.');
                }
                if ($path) {
                    AiMedia::create(['ai_generation_id' => $g->id, 'business_id' => $g->business_id,
                        'media_type' => 'image', 'file_url' => $path, 'width' => $width, 'height' => $height]);
                }
                $locked->update(['output_json' => $output, 'status' => 'completed', 'error' => null, 'error_code' => null]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            Log::warning('AI generation failed', ['generation_id' => $g->id, 'error_code' => $e instanceof AiProviderException ? $e->errorCode : 'generation_failed']);
            app(AiFailureService::class)->fail($g->id, $e);
        }
    }

    public function failed(?\Throwable $e): void
    {
        app(AiFailureService::class)->fail($this->generationId, $e);
    }
}
