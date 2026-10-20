<?php
namespace App\Console\Commands;
use App\Services\GrowyaCrmAdvancedService;
use Illuminate\Console\Command;
class GrowyaCrmFollowupReminders extends Command {
 protected $signature='growya:crm-reminders {--dry-run}';
 protected $description='Deliver deduplicated CRM follow-up notifications';
 public function handle(GrowyaCrmAdvancedService $service):int{$r=$service->sendDueReminders((bool)$this->option('dry-run'));$this->info(json_encode($r));return $r['failed']?self::FAILURE:self::SUCCESS;}
}
