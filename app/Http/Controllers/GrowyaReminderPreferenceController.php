<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GrowyaReminderPreferenceController extends Controller
{
 private const FIELDS=['wishlist_reminders','chat_reminders','subscription_reminders','promotion_reminders','support_reminders'];
 public function edit(Request $request){
  $settings=DB::table('growya_notification_preferences')->where('user_id',$request->user()->id)->first();
  return view('growya-reminders.preferences',compact('settings'));
 }
 public function update(Request $request){
  $data=$request->validate(array_fill_keys(self::FIELDS,['required','boolean']));
  DB::table('growya_notification_preferences')->updateOrInsert(
   ['user_id'=>$request->user()->id],array_merge($data,['updated_at'=>now()])
  );
  return back()->with('success','Reminder preferences saved.');
 }
}
