<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('growya_notification_preferences',function(Blueprint $t){
   $t->id();$t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
   $t->boolean('wishlist_reminders')->default(true);$t->boolean('chat_reminders')->default(true);
   $t->boolean('subscription_reminders')->default(true);$t->boolean('promotion_reminders')->default(true);
   $t->boolean('support_reminders')->default(true);$t->timestamps();
  });
  Schema::create('growya_reminder_deliveries',function(Blueprint $t){
   $t->id();$t->string('dedupe_key',190)->unique();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $t->string('reminder_type',40);$t->string('source_type',40);$t->unsignedBigInteger('source_id');
   $t->string('status',16)->default('reserved');$t->timestamp('sent_at')->nullable();$t->timestamp('attempted_at')->nullable();
   $t->text('last_error')->nullable();$t->timestamps();$t->index(['reminder_type','created_at']);
  });
 }
 public function down():void {Schema::dropIfExists('growya_reminder_deliveries');Schema::dropIfExists('growya_notification_preferences');}
};
