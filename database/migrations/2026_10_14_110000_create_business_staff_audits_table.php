<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('business_staff_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('business_staff_audits'); }
};
