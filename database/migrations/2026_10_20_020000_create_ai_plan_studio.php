<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->unsignedInteger('price_paise')->default(0);
            $t->string('currency', 3)->default('INR');
            $t->unsignedInteger('credits');
            $t->unsignedInteger('valid_days');
            $t->string('text_model')->nullable();
            $t->string('image_model')->nullable();
            $t->unsignedInteger('text_credits')->default(1);
            $t->unsignedInteger('image_credits')->default(10);
            $t->unsignedInteger('max_prompts')->default(3);
            $t->unsignedInteger('max_references')->default(2);
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });
        Schema::create('ai_plan_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->restrictOnDelete();
            $t->foreignId('ai_plan_id')->constrained()->restrictOnDelete();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('request_key', 100);
            $t->json('plan_snapshot');
            $t->string('status')->default('pending');
            $t->string('payment_reference')->nullable();
            $t->text('note')->nullable();
            $t->text('review_note')->nullable();
            $t->unsignedInteger('used_credits')->default(0);
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->unique(['business_id', 'request_key']);
            $t->index(['business_id', 'status', 'expires_at']);
        });
        Schema::create('ai_brand_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->unique()->constrained()->restrictOnDelete();
            $t->string('brand_name')->nullable();
            $t->string('tagline')->nullable();
            $t->string('contact')->nullable();
            $t->text('address')->nullable();
            $t->string('colors')->nullable();
            $t->string('language')->default('Hindi');
            $t->string('default_size')->default('1024x1024');
            $t->text('instructions')->nullable();
            $t->timestamps();
        });
        Schema::create('ai_brand_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->restrictOnDelete();
            $t->string('kind');
            $t->string('label');
            $t->string('path');
            $t->string('mime');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['business_id', 'kind']);
        });
        Schema::create('ai_prompt_presets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('generation_type');
            $t->text('prompt');
            $t->text('negative_prompt')->nullable();
            $t->json('reference_ids')->nullable();
            $t->string('size')->default('1024x1024');
            $t->string('language')->default('Hindi');
            $t->timestamps();
        });
        Schema::table('ai_generations', function (Blueprint $t) {
            $t->foreignId('ai_plan_request_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('error_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ai_plan_request_id');
            $t->dropColumn('error_code');
        });
        foreach (['ai_prompt_presets', 'ai_brand_assets', 'ai_brand_profiles', 'ai_plan_requests', 'ai_plans'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
