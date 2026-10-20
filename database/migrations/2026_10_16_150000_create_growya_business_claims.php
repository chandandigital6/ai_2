<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_business_claims', function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->foreignId('claimant_id')->constrained('users')->restrictOnDelete();
   $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
   $t->unsignedBigInteger('owner_id_at_submission')->nullable();
   $t->string('status',30)->default('pending')->index();
   $t->string('claimed_role',100); $t->text('reason'); $t->text('admin_note')->nullable();
   $t->timestamp('reviewed_at')->nullable(); $t->timestamps();
   $t->index(['business_id','status']); $t->index(['claimant_id','created_at']);
  });
  Schema::create('growya_business_claim_documents',function(Blueprint $t){
   $t->id(); $t->foreignId('claim_id')->constrained('growya_business_claims')->cascadeOnDelete();
   $t->string('document_type',50); $t->string('file_path'); $t->string('original_name');
   $t->string('mime_type',150)->nullable(); $t->unsignedBigInteger('file_size')->nullable(); $t->timestamps();
  });
  Schema::create('growya_business_claim_events',function(Blueprint $t){
   $t->id(); $t->foreignId('claim_id')->constrained('growya_business_claims')->cascadeOnDelete();
   $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
   $t->string('action',50); $t->text('notes')->nullable(); $t->timestamps();
  });
 }
 public function down(): void {
  Schema::dropIfExists('growya_business_claim_events'); Schema::dropIfExists('growya_business_claim_documents'); Schema::dropIfExists('growya_business_claims');
 }
};
