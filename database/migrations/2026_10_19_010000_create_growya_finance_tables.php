<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_finance_categories', function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->string('name',100); $t->string('kind',10); $t->timestamps();
   $t->unique(['business_id','kind','name'],'gfc_unique');
  });
  Schema::create('growya_finance_entries',function(Blueprint $t){
   $t->id(); $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
   $t->foreignId('category_id')->nullable()->constrained('growya_finance_categories')->nullOnDelete();
   $t->string('kind',10); $t->decimal('amount',14,2); $t->date('entry_date');
   $t->string('payment_method',20)->default('other'); $t->string('reference',140)->nullable();
   $t->string('description',500)->nullable(); $t->string('source_key',190)->nullable();
   $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
   $t->timestamps(); $t->softDeletes();
   $t->index(['business_id','entry_date','kind'],'gfe_business_date_kind');
   $t->unique(['business_id','source_key'],'gfe_business_source');
  });
 }
 public function down():void {Schema::dropIfExists('growya_finance_entries');Schema::dropIfExists('growya_finance_categories');}
};
