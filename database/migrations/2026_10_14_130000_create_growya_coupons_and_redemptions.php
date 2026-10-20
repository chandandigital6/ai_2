<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_coupons', function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->string('code',40); $t->enum('discount_type',['fixed','percent']);
   $t->decimal('discount_value',12,2); $t->decimal('minimum_subtotal',12,2)->default(0);
   $t->decimal('maximum_discount',12,2)->nullable(); $t->unsignedInteger('usage_limit')->nullable();
   $t->unsignedInteger('per_user_limit')->default(1); $t->unsignedInteger('used_count')->default(0);
   $t->timestamp('starts_at')->nullable(); $t->timestamp('ends_at')->nullable();
   $t->boolean('is_active')->default(true); $t->timestamps(); $t->unique(['business_id','code']);
  });
  Schema::create('growya_coupon_redemptions', function(Blueprint $t){
   $t->id(); $t->foreignId('coupon_id')->constrained('growya_coupons')->restrictOnDelete();
   $t->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
   $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
   $t->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
   $t->decimal('discount_amount',12,2); $t->timestamp('created_at')->useCurrent();
   $t->index(['coupon_id','user_id']);
  });
 }
 public function down(): void { Schema::dropIfExists('growya_coupon_redemptions'); Schema::dropIfExists('growya_coupons'); }
};
