<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class GrowyaReminderService
{
    private const PREFERENCES = [
        'wishlist' => 'wishlist_reminders', 'chat' => 'chat_reminders',
        'subscription' => 'subscription_reminders', 'promotion' => 'promotion_reminders',
        'support' => 'support_reminders',
    ];

    public function dispatch(bool $dryRun = false): array
    {
        $counts = ['wishlist'=>0,'chat'=>0,'subscription'=>0,'promotion'=>0,'support'=>0,'failed'=>0];
        $today = now()->startOfDay();
        $endOfToday = $today->copy()->endOfDay();
        $tomorrow = $today->copy()->addDay();

        // Wishlist follow-ups: sent on due date, even after scheduler downtime.
        DB::table('wishlist_interests')->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', $endOfToday)
            ->whereNotIn('status',['converted','closed'])
            ->orderBy('id')->chunkById(100,function($rows) use (&$counts,$dryRun,$today){
                foreach($rows as $row){
                    $owner = DB::table('businesses')->where('id',$row->business_id)->where('status','active')->value('owner_id');
                    if(!$owner)continue;
                    $date = Carbon::parse($row->follow_up_at)->toDateString();
                    $this->deliver($counts,'wishlist',(int)$row->id,(int)$owner,$date,'Wishlist follow-up due',
                        'Follow up on a customer wishlist interest (business #'.$row->business_id.').',
                        '/businesses/'.$row->business_id.'/wishlist-interests',(int)$row->business_id,$dryRun);
                }
            });

        DB::table('growya_chat_threads')->whereNotNull('followup_at')
            ->where('followup_at','<=',$endOfToday)->whereNotIn('followup_status',['converted','closed'])
            ->orderBy('id')->chunkById(100,function($rows)use(&$counts,$dryRun){
                foreach($rows as $row){
                    $owner=DB::table('businesses')->where('id',$row->business_id)->where('status','active')->value('owner_id');
                    $recipients=array_unique(array_filter([(int)$owner,(int)$row->assigned_to]));
                    $day=Carbon::parse($row->followup_at)->toDateString();
                    foreach($recipients as $recipient){
                        // Staff assignment must still be active. Former staff must not receive alerts.
                        if($recipient!== (int)$owner && !DB::table('business_users')->where('business_id',$row->business_id)->where('user_id',$recipient)->where('is_active',1)->exists())continue;
                        $this->deliver($counts,'chat',(int)$row->id,$recipient,$day,'Chat follow-up due',
                            'A customer conversation needs follow-up.', '/messages/'.$row->id,(int)$row->business_id,$dryRun);
                    }
                }
            });

        // Date-based reminders, not every-time polling. One per threshold and recipient.
        foreach ([7,3,1] as $days){
            $date=$today->copy()->addDays($days)->toDateString();
            DB::table('subscriptions')->where('status','active')->whereDate('ends_at',$date)->orderBy('id')
              ->chunkById(100,function($rows)use(&$counts,$dryRun,$date,$days){
                  foreach($rows as $row){
                      $owner=DB::table('businesses')->where('id',$row->business_id)->where('status','active')->value('owner_id');
                      if($owner)$this->deliver($counts,'subscription',(int)$row->id,(int)$owner,$date.'-'.$days,
                          'Subscription expires in '.$days.' day(s)', 'Review or renew your Growya subscription.',
                          '/businesses/'.$row->business_id.'/subscriptions',(int)$row->business_id,$dryRun);
                  }
              });
        }
        foreach ([3,1] as $days){
            $date=$today->copy()->addDays($days)->toDateString();
            DB::table('growya_paid_promotions')->where('status','active')->whereDate('ends_at',$date)->orderBy('id')
              ->chunkById(100,function($rows)use(&$counts,$dryRun,$date,$days){
                  foreach($rows as $row){
                      $owner=DB::table('businesses')->where('id',$row->business_id)->where('status','active')->value('owner_id');
                      if($owner)$this->deliver($counts,'promotion',(int)$row->id,(int)$owner,$date.'-'.$days,
                          'Promotion expires in '.$days.' day(s)', 'Your sponsored promotion is ending soon.',
                          '/businesses/'.$row->business_id.'/paid-promotions',(int)$row->business_id,$dryRun);
                  }
              });
        }

        // Overdue support tickets: configurable threshold (48h since last reply, otherwise creation).
        $threshold=now()->subHours(48);
        DB::table('growya_support_tickets')->whereIn('status',['open','in_progress'])
            ->whereRaw('COALESCE(last_reply_at,created_at) <= ?',[$threshold])
            ->orderBy('id')->chunkById(100,function($rows)use(&$counts,$dryRun,$today){
                foreach($rows as $row){
                    $recipient=$row->assigned_to ?: null;
                    if(!$recipient){
                        // No assigned agent: notify super admins, not ticket creator.
                        foreach(User::role('super_admin','web')->pluck('id') as $adminId){
                            $this->deliver($counts,'support',(int)$row->id,(int)$adminId,$today->toDateString(),
                                'Support ticket overdue', 'Ticket '.$row->ticket_number.' needs attention.',
                                '/support/tickets/'.$row->id,$row->business_id ? (int)$row->business_id:null,$dryRun);
                        }
                    }else{
                        $this->deliver($counts,'support',(int)$row->id,(int)$recipient,$today->toDateString(),
                            'Support ticket overdue','Ticket '.$row->ticket_number.' needs attention.',
                            '/support/tickets/'.$row->id,$row->business_id ? (int)$row->business_id:null,$dryRun);
                    }
                }
            });
        return $counts;
    }

    private function deliver(array &$counts,string $type,int $sourceId,int $userId,string $cycle,string $title,string $body,string $url,?int $businessId,bool $dryRun):void
    {
        if($userId<1)return;
        $column=self::PREFERENCES[$type];
        $prefs=DB::table('growya_notification_preferences')->where('user_id',$userId)->first();
        if($prefs && !$prefs->{$column})return;
        if(!User::query()->whereKey($userId)->where('status','active')->exists())return;
        $key=hash('sha256',implode('|',[$type,$sourceId,$userId,$cycle]));
        if($dryRun){if(!DB::table('growya_reminder_deliveries')->where('dedupe_key',$key)->where('status','sent')->exists())$counts[$type]++;return;}
        $inserted=DB::table('growya_reminder_deliveries')->insertOrIgnore([
            'dedupe_key'=>$key,'user_id'=>$userId,'reminder_type'=>$type,'source_type'=>$type,
            'source_id'=>$sourceId,'status'=>'reserved','attempted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        if(!$inserted)return;
        try{
            app(GrowyaNotificationService::class)->send([$userId],$type.'_reminder',$title,$body,$url,$businessId);
            DB::table('growya_reminder_deliveries')->where('dedupe_key',$key)->update(['status'=>'sent','sent_at'=>now(),'updated_at'=>now()]);
            $counts[$type]++;
        }catch(\Throwable $e){
            DB::table('growya_reminder_deliveries')->where('dedupe_key',$key)->update(['status'=>'failed','last_error'=>mb_substr($e->getMessage(),0,4000),'updated_at'=>now()]);
            $counts['failed']++;Log::error('Growya reminder failed',['type'=>$type,'source_id'=>$sourceId,'exception'=>$e->getMessage()]);
        }
    }
}
