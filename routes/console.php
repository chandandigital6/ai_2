<?php

use App\Services\Extensions\AnalyticsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(
    'growya:send-reminders'
)->dailyAt('09:00')->withoutOverlapping();

Schedule::command('growya:crm-sync --limit=20000')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('growya:crm-reminders')
    ->dailyAt('09:15')
    ->withoutOverlapping();

Schedule::command('growya:extensions')->everyMinute()->withoutOverlapping();
Schedule::call(function () {
    app(AnalyticsService::class)->aggregate(today()->toDateString());
    app(AnalyticsService::class)->aggregate(today()->subDay()->toDateString());
})->name('growya-daily-analytics')->hourly()->withoutOverlapping();

Schedule::command('growya:ai-recover')->everyMinute()->withoutOverlapping();
