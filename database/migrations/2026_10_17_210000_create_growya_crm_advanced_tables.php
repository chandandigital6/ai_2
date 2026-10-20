<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_crm_source_links',function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->foreignId('contact_id')->constrained('growya_crm_contacts')->cascadeOnDelete();
   $t->string('source_type',30);$t->unsignedBigInteger('source_id');$t->timestamps();
   $t->unique(['source_type','source_id'],'growya_crm_source_unique');$t->index(['business_id','contact_id']);
  });
  Schema::create('growya_crm_followup_deliveries',function(Blueprint $t){
   $t->id();$t->foreignId('contact_id')->constrained('growya_crm_contacts')->cascadeOnDelete();
   $t->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
   $t->string('due_key',64);$t->string('status',20)->default('reserved');$t->text('error')->nullable();$t->timestamps();
   $t->unique(['contact_id','recipient_id','due_key'],'growya_crm_followup_unique');
  });
 }
 public function down(): void {Schema::dropIfExists('growya_crm_followup_deliveries');Schema::dropIfExists('growya_crm_source_links');}
};
