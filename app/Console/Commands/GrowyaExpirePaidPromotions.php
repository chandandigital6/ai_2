<?php
namespace App\Console\Commands;
use App\Models\GrowyaPaidPromotion;
use App\Services\GrowyaNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class GrowyaExpirePaidPromotions extends Command {
 protected $signature='growya:promotions-expire';protected $description='Expire approved paid promotions whose end date has passed';
 public function handle():int {
  GrowyaPaidPromotion::where('status','active')->where('ends_at','<=',now())->orderBy('id')->chunkById(100,function($items){foreach($items as $item){$changed=DB::transaction(function()use($item){$p=GrowyaPaidPromotion::whereKey($item->id)->lockForUpdate()->first();if(!$p||$p->status!=='active'||$p->ends_at>now())return false;$p->update(['status'=>'expired']);DB::table('growya_promotion_events')->insert(['promotion_id'=>$p->id,'event'=>'expired','actor_id'=>null,'notes'=>null,'created_at'=>now(),'updated_at'=>now()]);return true;});if($changed){app(GrowyaNotificationService::class)->send([(int)$item->business->owner_id],'promotion_expired','Promotion expired','Your paid promotion has ended.','/businesses/'.$item->business_id.'/paid-promotions',$item->business_id);}}});
  $this->info('Expired promotions updated.');return self::SUCCESS;
 }
}
