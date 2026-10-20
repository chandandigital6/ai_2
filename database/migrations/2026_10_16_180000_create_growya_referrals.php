<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_referral_codes', function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();$t->string('code',20)->unique();$t->timestamps();});
  Schema::create('growya_referral_settings',function(Blueprint $t){$t->id();$t->unsignedInteger('customer_points')->default(20);$t->unsignedInteger('business_points')->default(100);$t->boolean('enabled')->default(true);$t->timestamps();});
  Schema::create('growya_referrals',function(Blueprint $t){$t->id();$t->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();$t->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();$t->string('referral_code',20);$t->timestamp('customer_rewarded_at')->nullable();$t->timestamp('business_rewarded_at')->nullable();$t->foreignId('rewarded_business_id')->nullable()->constrained('businesses')->nullOnDelete();$t->timestamps();$t->index(['referrer_id','created_at']);});
  Schema::create('growya_reward_transactions',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();$t->foreignId('referral_id')->constrained('growya_referrals')->cascadeOnDelete();$t->string('reward_type',24);$t->unsignedInteger('points');$t->text('description')->nullable();$t->timestamps();$t->unique(['referral_id','reward_type']);$t->index(['user_id','created_at']);});
 }
 public function down():void {Schema::dropIfExists('growya_reward_transactions');Schema::dropIfExists('growya_referrals');Schema::dropIfExists('growya_referral_settings');Schema::dropIfExists('growya_referral_codes');}
};