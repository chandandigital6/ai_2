<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_promotion_packages',function(Blueprint $t){$t->id();$t->string('name',120);$t->enum('target_type',['business','product']);$t->unsignedInteger('duration_days');$t->decimal('price',12,2);$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('growya_paid_promotions',function(Blueprint $t){$t->id();$t->foreignId('package_id')->constrained('growya_promotion_packages')->restrictOnDelete();$t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();$t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();$t->foreignId('requested_by')->constrained('users')->restrictOnDelete();$t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();$t->string('status',25)->default('pending')->index();$t->string('payment_reference',150)->nullable();$t->string('payment_status',25)->default('unverified');$t->text('review_note')->nullable();$t->timestamp('reviewed_at')->nullable();$t->timestamp('starts_at')->nullable();$t->timestamp('ends_at')->nullable();$t->unsignedBigInteger('impressions')->default(0);$t->unsignedBigInteger('clicks')->default(0);$t->timestamps();$t->index(['business_id','status','starts_at','ends_at'],'growya_promo_active_idx');$t->index(['product_id','status']);});
  Schema::create('growya_promotion_events',function(Blueprint $t){$t->id();$t->foreignId('promotion_id')->constrained('growya_paid_promotions')->cascadeOnDelete();$t->string('event',30);$t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();$t->text('notes')->nullable();$t->timestamps();$t->index(['promotion_id','event']);});
 }
 public function down():void {Schema::dropIfExists('growya_promotion_events');Schema::dropIfExists('growya_paid_promotions');Schema::dropIfExists('growya_promotion_packages');}
};
