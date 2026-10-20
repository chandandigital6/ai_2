<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('growya_crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('company', 190)->nullable();
            $table->string('status', 30)->default('new');
            $table->string('source', 40)->default('manual');
            $table->string('tags', 500)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['business_id','email'], 'growya_crm_business_email_unique');
            $table->index(['business_id','status']);
            $table->index(['business_id','next_follow_up_at']);
        });
        Schema::create('growya_crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('growya_crm_contacts')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->text('description');
            $table->timestamps();
            $table->index(['business_id','contact_id','created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('growya_crm_activities');
        Schema::dropIfExists('growya_crm_contacts');
    }
};
