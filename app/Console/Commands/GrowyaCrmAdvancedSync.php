<?php
namespace App\Console\Commands;
use App\Services\GrowyaCrmAdvancedService;
use Illuminate\Console\Command;
class GrowyaCrmAdvancedSync extends Command {
 protected $signature='growya:crm-sync {--limit=1000 : Max source records evaluated}';
 protected $description='Safely link authenticated leads to business CRM contacts';
 public function handle(GrowyaCrmAdvancedService $service):int { $r=$service->sync((int)$this->option('limit'));$this->info(json_encode($r));return self::SUCCESS;}
}
