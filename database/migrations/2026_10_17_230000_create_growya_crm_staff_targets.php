<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_crm_staff_targets',function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $t->string('period',10); $t->date('period_start');
   $t->unsignedInteger('calls_target')->default(0); $t->unsignedInteger('followups_target')->default(0);
   $t->unsignedInteger('conversions_target')->default(0); $t->timestamps();
   $t->unique(['business_id','user_id','period','period_start'],'crm_staff_target_unique');
  });
 }
 public function down(): void { Schema::dropIfExists('growya_crm_staff_targets'); }
};
