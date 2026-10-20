<?php
namespace App\Services;
use App\Models\GrowyaCrmContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class GrowyaCrmAdvancedService {
 private const SOURCES = [
  'product_enquiry'=>['business_enquiries','user_id','product_id'],
  'service_enquiry'=>['service_enquiries','user_id','service_id'],
  'wishlist'=>['wishlist_interests','user_id',null],
  'chat'=>['growya_chat_threads','customer_id',null],
 ];
 public function sync(int $limit = 1000): array {
  $added=0;$skipped=0;$limit=max(1,min($limit,20000));
  foreach(self::SOURCES as $type=>[$table,$userColumn,$required]){
   if(!Schema::hasTable($table))continue;
   $query=DB::table($table)->whereNotNull($userColumn);
   if($required && $type==='product_enquiry')$query->whereNotNull($required);
   $query->orderBy('id')->chunkById(250,function($rows)use($type,$userColumn,&$added,&$skipped,$limit){
    foreach($rows as $row){
     if($added+$skipped >=$limit)return false;
     $uid=(int)$row->{$userColumn};$bid=(int)$row->business_id;
     if(!$uid||!$bid){$skipped++;continue;}
     if(DB::table('growya_crm_source_links')->where('source_type',$type)->where('source_id',$row->id)->exists()){$skipped++;continue;}
     DB::transaction(function()use($type,$row,$uid,$bid,&$added,&$skipped){
      $existing=GrowyaCrmContact::where('business_id',$bid)->where('user_id',$uid)->first();
      if(!$existing){
       // Link only on exact authenticated user id; never guess identity by name/phone/email.
       $user=DB::table('users')->where('id',$uid)->first(['id','name','email','phone']);
       if(!$user){$skipped++;return;}
       // Avoid overwriting or merging manually added contacts with matching email.
       $email=$user->email?mb_strtolower(trim($user->email)):null;
       if($email && GrowyaCrmContact::where('business_id',$bid)->where('email',$email)->exists()){$skipped++;return;}
       $existing=GrowyaCrmContact::create([
        'business_id'=>$bid,'user_id'=>$uid,'name'=>mb_substr($user->name?:'Customer',0,150),
        'email'=>$email,'phone'=>$user->phone,'status'=>'new','source'=>$type,
       ]);
       DB::table('growya_crm_activities')->insert(['business_id'=>$bid,'contact_id'=>$existing->id,'actor_id'=>null,'type'=>'created','description'=>'CRM contact created from '.$type,'created_at'=>now(),'updated_at'=>now()]);
      }
      $ok=DB::table('growya_crm_source_links')->insertOrIgnore(['business_id'=>$bid,'contact_id'=>$existing->id,'source_type'=>$type,'source_id'=>$row->id,'created_at'=>now(),'updated_at'=>now()]);
      if($ok)$added++;else $skipped++;
     });
    }
   });
  }
  return compact('added','skipped');
 }
 public function sendDueReminders(bool $dryRun=false):array {
  $result=['eligible'=>0,'sent'=>0,'failed'=>0];
  GrowyaCrmContact::whereNotNull('next_follow_up_at')->where('next_follow_up_at','<=',now())
   ->whereNotIn('status',['converted','closed'])->orderBy('id')->chunkById(200,function($contacts)use(&$result,$dryRun){
    foreach($contacts as $c){
     $business=DB::table('businesses')->where('id',$c->business_id)->where('status','active')->first(['owner_id']);
     if(!$business)continue;
     $recipient=(int)$business->owner_id;
     if($c->assigned_to && DB::table('business_users')->where('business_id',$c->business_id)->where('user_id',$c->assigned_to)->where('is_active',true)->exists())$recipient=(int)$c->assigned_to;
     if(!DB::table('users')->where('id',$recipient)->where('status','active')->exists())continue;
     $key=hash('sha256',$c->next_follow_up_at->format('Y-m-d H:i:s'));
     if(DB::table('growya_crm_followup_deliveries')->where('contact_id',$c->id)->where('recipient_id',$recipient)->where('due_key',$key)->exists())continue;
     $result['eligible']++;
     if($dryRun)continue;
     $row=DB::table('growya_crm_followup_deliveries')->insertOrIgnore(['contact_id'=>$c->id,'recipient_id'=>$recipient,'due_key'=>$key,'status'=>'reserved','created_at'=>now(),'updated_at'=>now()]);
     if(!$row)continue;
     try{
      app(GrowyaNotificationService::class)->send([$recipient],'crm_followup','CRM follow-up due','Follow up with '.$c->name,'/businesses/'.$c->business_id.'/crm/'.$c->id,(int)$c->business_id);
      DB::table('growya_crm_followup_deliveries')->where('contact_id',$c->id)->where('recipient_id',$recipient)->where('due_key',$key)->update(['status'=>'sent','updated_at'=>now()]);
      $result['sent']++;
     }catch(\Throwable $e){report($e);DB::table('growya_crm_followup_deliveries')->where('contact_id',$c->id)->where('recipient_id',$recipient)->where('due_key',$key)->update(['status'=>'failed','error'=>mb_substr($e->getMessage(),0,2000),'updated_at'=>now()]);$result['failed']++;}
    }
   });
  return $result;
 }
}
