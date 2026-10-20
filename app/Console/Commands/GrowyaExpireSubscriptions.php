<?php
namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\GrowyaNotificationService;
use Illuminate\Console\Command;

class GrowyaExpireSubscriptions extends Command
{
    protected $signature = 'growya:subscriptions-expire';
    protected $description = 'Expire subscriptions past their end date';

    public function handle(GrowyaNotificationService $notifications): int
    {
        Subscription::query()->where('status','active')->whereNotNull('ends_at')->where('ends_at','<=',now())
            ->orderBy('id')->chunkById(100, function($subs) use ($notifications) {
                foreach($subs as $sub) {
                    $changed=Subscription::whereKey($sub->id)->where('status','active')->where('ends_at','<=',now())->update(['status'=>'expired']);
                    if($changed) $notifications->send($notifications->ownerOf($sub->business_id),'subscription_expired',
                        'Subscription expired','Your Growya subscription has expired. You can request a new plan.',
                        '/businesses/'.$sub->business_id.'/subscriptions',$sub->business_id);
                }
            });
        $this->info('Expired subscriptions processed.');
        return self::SUCCESS;
    }
}
