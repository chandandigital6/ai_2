<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('growya_business_plan_overrides',function(Blueprint $table){
            $table->id(); $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('feature_key',60); $table->string('value',30);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason'); $table->timestamp('expires_at')->nullable(); $table->timestamps();
            $table->unique(['business_id','feature_key']);
        });
        Schema::create('growya_plan_override_audits',function(Blueprint $table){
            $table->id(); $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('feature_key',60); $table->string('old_value',30)->nullable();
            $table->string('new_value',30)->nullable(); $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason'); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('growya_plan_override_audits'); Schema::dropIfExists('growya_business_plan_overrides'); }
};
