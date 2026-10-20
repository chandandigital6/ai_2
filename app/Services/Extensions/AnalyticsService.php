<?php

namespace App\Services\Extensions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function aggregate(string $date): void
    {
        $start = Carbon::parse($date, config('app.timezone'))->startOfDay();
        $end = $start->copy()->addDay();
        DB::table('businesses')->select('id')->orderBy('id')->chunkById(200, function ($businesses) use ($date, $start, $end) {
            foreach ($businesses as $b) {
                $events = DB::table('activity_events')->where('business_id', $b->id)->where('created_at', '>=', $start)->where('created_at', '<', $end)->selectRaw('event_type, COUNT(*) as n')->groupBy('event_type')->pluck('n', 'event_type');
                $count = fn ($table, $column = 'created_at') => DB::table($table)->where('business_id', $b->id)->where($column, '>=', $start)->where($column, '<', $end)->count();
                $notice = DB::table('notification_recipients as r')->join('notifications as n', 'n.id', '=', 'r.notification_id')->where('n.business_id', $b->id);
                DB::table('business_daily_analytics')->updateOrInsert(['business_id' => $b->id, 'date' => $date], [
                    'views' => (int) ($events['view'] ?? 0), 'clicks' => (int) ($events['call_click'] ?? 0) + (int) ($events['whatsapp_click'] ?? 0) + (int) ($events['map_click'] ?? 0),
                    'shares' => (int) ($events['share'] ?? 0) + (int) ($events['share_click'] ?? 0), 'orders' => $count('orders'), 'followers' => $count('business_followers'),
                    'enquiries' => $count('business_enquiries') + $count('service_enquiries'),
                    'notifications_sent' => (clone $notice)->where('r.sent_at', '>=', $start)->where('r.sent_at', '<', $end)->count(),
                    'notifications_opened' => (clone $notice)->where('r.opened_at', '>=', $start)->where('r.opened_at', '<', $end)->count(), 'updated_at' => now(), 'created_at' => now(),
                ]);
            }
        });
    }
}
