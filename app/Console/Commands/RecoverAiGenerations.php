<?php

namespace App\Console\Commands;

use App\Exceptions\AiProviderException;
use App\Models\AiGeneration;
use App\Services\Extensions\AiFailureService;
use Illuminate\Console\Command;

class RecoverAiGenerations extends Command
{
    protected $signature = 'growya:ai-recover';

    protected $description = 'Refund abandoned AI jobs after a worker outage';

    public function handle(AiFailureService $failures): int
    {
        AiGeneration::where(function ($q) {
            $q->where(fn ($q) => $q->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(6)))
                ->orWhere(fn ($q) => $q->where('status', 'queued')->where('created_at', '<', now()->subMinutes(30)));
        })->eachById(fn ($g) => $failures->fail($g->id, new AiProviderException('worker_timeout', 'Generation expired while waiting for the AI worker. Contact the administrator.'), true));

        return self::SUCCESS;
    }
}
