<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 14, 2);
            $table->decimal('offer_price', 14, 2)->nullable();
            $table->string('stock_status');
            $table->integer('stock_quantity')->default(0);
            $table->string('featured_image')->nullable();
            $table->string('video')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'category_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('image');
            $table->string('image_type');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('price', 14, 2);
            $table->decimal('offer_price', 14, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('image')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'tag_id']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->restrictOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_value_id']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('offer_price', 14, 2)->nullable();
            $table->string('price_type')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'slug']);
        });

        Schema::create('service_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->string('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('business_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->string('media_type');
            $table->string('file_url');
            $table->string('thumbnail')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('offer_type');
            $table->decimal('discount_value', 14, 2)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('banner')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'status']);
        });

        Schema::create('offer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['offer_id', 'product_id']);
        });

        Schema::create('offer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['offer_id', 'service_id']);
        });

        Schema::create('business_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->boolean('notifications_enabled')->default(false);
            $table->dateTime('followed_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('business_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('product_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->index(['business_id', 'user_id']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->json('address_snapshot')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2);
            $table->decimal('tax_amount', 14, 2);
            $table->decimal('total_amount', 14, 2);
            $table->string('payment_method')->nullable();
            $table->string('payment_status');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total_price', 14, 2);
            $table->json('product_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('business_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->text('message')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('service_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->text('message')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(0);
            $table->text('review')->nullable();
            $table->string('status');
            $table->text('business_reply')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();
            $table->index(['business_id', 'status']);
        });

        Schema::create('review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->restrictOnDelete();
            $table->string('image');
            $table->timestamps();
        });

        Schema::create('recently_viewed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('viewable_type');
            $table->unsignedInteger('viewable_id')->default(0);
            $table->dateTime('viewed_at');
            $table->timestamps();
            $table->index(['viewable_type', 'viewable_id']);
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('label')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address');
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('pincode')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('image')->nullable();
            $table->string('action_url')->nullable();
            $table->string('target_type')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('fcm_token');
            $table->string('platform');
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(false);
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('generation_type');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->text('prompt')->nullable();
            $table->json('input_json')->nullable();
            $table->json('output_json')->nullable();
            $table->string('language')->nullable();
            $table->unsignedInteger('credits_used')->default(0);
            $table->decimal('cost', 14, 2)->nullable();
            $table->string('status');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'created_at']);
        });

        Schema::create('ai_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_generation_id')->constrained('ai_generations')->restrictOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->string('media_type');
            $table->string('file_url');
            $table->string('thumbnail')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->unsignedInteger('total_credits')->default(0);
            $table->unsignedInteger('used_credits')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->string('type');
            $table->integer('credits')->default(0);
            $table->integer('balance_after')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'created_at']);
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->string('platform');
            $table->string('external_id')->nullable();
            $table->string('account_name')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('connection_status');
            $table->timestamps();
            $table->index(['business_id', 'platform']);
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->text('caption')->nullable();
            $table->string('post_type');
            $table->string('publish_mode');
            $table->dateTime('scheduled_at')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->string('overall_status');
            $table->timestamps();
            $table->index(['business_id', 'overall_status']);
        });

        Schema::create('social_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->restrictOnDelete();
            $table->string('media_type');
            $table->string('file_url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('social_post_platforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->restrictOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->restrictOnDelete();
            $table->string('external_post_id')->nullable();
            $table->text('external_post_url')->nullable();
            $table->string('status');
            $table->text('error')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->unique(['social_post_id', 'social_account_id']);
        });

        Schema::create('social_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->string('frequency');
            $table->time('run_time');
            $table->json('days_of_week')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('festivals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('festival_date');
            $table->json('region_filters')->nullable();
            $table->text('default_caption')->nullable();
            $table->text('default_prompt')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->string('event_type');
            $table->string('source')->nullable();
            $table->string('platform')->nullable();
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'event_type', 'created_at']);
        });

        Schema::create('business_daily_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('followers')->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->unsignedInteger('enquiries')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('notifications_sent')->default(0);
            $table->unsignedInteger('notifications_opened')->default(0);
            $table->timestamps();
            $table->unique(['business_id', 'date']);
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('query');
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->timestamps();
        });

        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->string('shareable_type');
            $table->unsignedInteger('shareable_id')->default(0);
            $table->string('code')->unique();
            $table->string('source')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->index(['shareable_type', 'shareable_id']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 14, 2);
            $table->decimal('yearly_price', 14, 2);
            $table->unsignedInteger('trial_days')->default(0);
            $table->json('flags')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->unique();
            $table->string('name');
            $table->string('data_type');
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'feature_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('billing_cycle');
            $table->decimal('amount', 14, 2);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->index(['business_id', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status');
            $table->dateTime('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('business_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->string('file_url');
            $table->string('verification_status');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('business_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('source')->nullable();
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('total_order_value', 14, 2);
            $table->dateTime('last_order_at')->nullable();
            $table->dateTime('last_contact_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'phone']);
        });

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_customer_id')->constrained('business_customers')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type');
            $table->unsignedInteger('reportable_id')->default(0);
            $table->text('reason');
            $table->string('status');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('handled_at')->nullable();
            $table->timestamps();
            $table->index(['reportable_type', 'reportable_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedInteger('auditable_id')->default(0);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group_name');
            $table->string('key_name');
            $table->text('value')->nullable();
            $table->string('value_type');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['group_name', 'key_name']);
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement');
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('media');
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('business_customers');
        Schema::dropIfExists('business_documents');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('share_links');
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('business_daily_analytics');
        Schema::dropIfExists('activity_events');
        Schema::dropIfExists('festivals');
        Schema::dropIfExists('social_schedules');
        Schema::dropIfExists('social_post_platforms');
        Schema::dropIfExists('social_post_media');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('ai_credit_transactions');
        Schema::dropIfExists('ai_credit_wallets');
        Schema::dropIfExists('ai_media');
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('recently_viewed');
        Schema::dropIfExists('review_images');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('service_enquiries');
        Schema::dropIfExists('business_enquiries');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('product_favourites');
        Schema::dropIfExists('business_favourites');
        Schema::dropIfExists('business_followers');
        Schema::dropIfExists('offer_services');
        Schema::dropIfExists('offer_products');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('business_gallery');
        Schema::dropIfExists('service_images');
        Schema::dropIfExists('services');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
