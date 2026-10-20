<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_crm_incentive_rules',function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->string('name',120); $t->string('basis',25); // crm_conversion or manually_verified_sale
   $t->string('calculation',15); // fixed or percentage
   $t->decimal('amount',12,2); $t->boolean('active')->default(true);
   $t->timestamps(); $t->index(['business_id','active']);
  });
  Schema::create('growya_crm_incentive_claims',function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
   $t->foreignId('contact_id')->nullable()->constrained('growya_crm_contacts')->nullOnDelete();
   $t->foreignId('rule_id')->nullable()->constrained('growya_crm_incentive_rules')->nullOnDelete();
   $t->string('basis',25); $t->string('status',20)->default('pending');
   $t->decimal('base_amount',14,2)->nullable(); $t->decimal('rate_snapshot',12,2);
   $t->string('calculation_snapshot',15); $t->decimal('incentive_amount',14,2);
   $t->string('source_reference',160)->nullable(); $t->text('note')->nullable();
   $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
   $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
   $t->timestamp('reviewed_at')->nullable(); $t->string('payment_reference',160)->nullable();
   $t->timestamp('paid_at')->nullable(); $t->timestamps();
   $t->index(['business_id','staff_user_id','status'],'gci_business_staff_status');
   $t->index(['business_id','created_at']);
  });
  Schema::create('growya_crm_incentive_audits',function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->foreignId('claim_id')->constrained('growya_crm_incentive_claims')->cascadeOnDelete();
   $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
   $t->string('action',35); $t->text('note')->nullable(); $t->timestamps();
  });
 }
 public function down():void { Schema::dropIfExists('growya_crm_incentive_audits'); Schema::dropIfExists('growya_crm_incentive_claims'); Schema::dropIfExists('growya_crm_incentive_rules'); }
};
