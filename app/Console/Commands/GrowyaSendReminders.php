<?php
namespace App\Console\Commands;
use App\Services\GrowyaReminderService;
use Illuminate\Console\Command;
class GrowyaSendReminders extends Command
{
 protected $signature='growya:send-reminders {--dry-run : Preview counts without sending}';
 protected $description='Send due Growya follow-up and expiry alerts';
 public function handle(GrowyaReminderService $service):int {
  $result=$service->dispatch((bool)$this->option('dry-run'));
  foreach($result as $name=>$count)$this->line($name.': '.$count);
  return self::SUCCESS;
 }
}
