<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_devices', function (Blueprint $t) {
            $t->string('token_hash', 64)->nullable()->unique();
            $t->unsignedBigInteger('session_token_id')->nullable()->index();
        });
        Schema::table('ai_generations', function (Blueprint $t) {
            $t->string('request_key', 100)->nullable();
            $t->unique(['business_id', 'request_key']);
        });
        Schema::table('ai_credit_transactions', function (Blueprint $t) {
            $t->string('request_key', 100)->nullable();
            $t->unique(['business_id', 'request_key']);
        });
        Schema::table('social_posts', function (Blueprint $t) {
            $t->string('request_key', 100)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unique(['business_id', 'request_key']);
        });
        Schema::table('social_schedules', function (Blueprint $t) {
            $t->text('caption')->nullable();
            $t->string('image_path')->nullable();
            $t->string('timezone', 64)->default('Asia/Kolkata');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('social_post_platforms', function (Blueprint $t) {
            $t->string('container_id')->nullable();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->decimal('shipping_amount', 14, 2)->default(0);
            $t->json('tax_snapshot')->nullable();
        });
        Schema::create('growya_push_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('recipient_id')->constrained('notification_recipients')->cascadeOnDelete();
            $t->foreignId('device_id')->constrained('user_devices')->cascadeOnDelete();
            $t->string('status')->default('queued');
            $t->string('provider_message_id')->nullable();
            $t->unsignedInteger('attempts')->default(0);
            $t->string('error_code')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('opened_at')->nullable();
            $t->timestamps();
            $t->unique(['recipient_id', 'device_id']);
        });
        Schema::create('growya_commerce_policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->unique()->constrained('businesses')->restrictOnDelete();
            $t->boolean('shipping_enabled')->default(false);
            $t->unsignedInteger('shipping_paise')->default(0);
            $t->unsignedInteger('free_above_paise')->nullable();
            $t->boolean('tax_enabled')->default(false);
            $t->unsignedInteger('tax_basis_points')->default(0);
            $t->string('seller_state', 100)->nullable();
            $t->boolean('delivery_otp_required')->default(false);
            $t->boolean('delivery_proof_required')->default(false);
            $t->timestamps();
        });
        Schema::create('growya_delivery_proofs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $t->string('otp_hash')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('verified_at')->nullable();
            $t->string('file_path')->nullable();
            $t->string('recipient_name', 120)->nullable();
            $t->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('growya_gateway_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $t->string('request_key', 100);
            $t->string('provider_order_id')->nullable()->unique();
            $t->string('provider_payment_id')->nullable()->unique();
            $t->unsignedBigInteger('amount_paise');
            $t->string('currency', 3)->default('INR');
            $t->string('status')->default('creating');
            $t->timestamps();
            $t->unique(['order_id', 'request_key']);
        });
        Schema::create('growya_journal_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $t->string('code', 30);
            $t->string('name', 150);
            $t->string('type', 20);
            $t->boolean('is_cash')->default(false);
            $t->timestamps();
            $t->unique(['business_id', 'code']);
        });
        Schema::create('growya_journals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('request_key', 100);
            $t->string('request_hash', 64);
            $t->date('entry_date');
            $t->string('memo', 500);
            $t->foreignId('reverses_id')->nullable()->constrained('growya_journals')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['business_id', 'request_key']);
            $t->unique('reverses_id');
        });
        Schema::create('growya_journal_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_id')->constrained('growya_journals')->restrictOnDelete();
            $t->foreignId('account_id')->constrained('growya_journal_accounts')->restrictOnDelete();
            $t->unsignedBigInteger('debit_paise')->default(0);
            $t->unsignedBigInteger('credit_paise')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['growya_journal_lines', 'growya_journals', 'growya_journal_accounts', 'growya_gateway_payments', 'growya_delivery_proofs', 'growya_commerce_policies', 'growya_push_deliveries'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['shipping_amount', 'tax_snapshot']));
        Schema::table('social_post_platforms', fn (Blueprint $t) => $t->dropColumn('container_id'));
        Schema::table('social_schedules', function (Blueprint $t) {
            $t->dropConstrainedForeignId('created_by');
            $t->dropColumn(['caption', 'image_path', 'timezone']);
        });
        Schema::table('social_posts', function (Blueprint $t) {
            $t->dropUnique(['business_id', 'request_key']);
            $t->dropConstrainedForeignId('created_by');
            $t->dropColumn('request_key');
        });
        foreach (['ai_credit_transactions', 'ai_generations'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique(['business_id', 'request_key']);
                $t->dropColumn('request_key');
            });
        }
        Schema::table('user_devices', function (Blueprint $t) {
            $t->dropUnique(['token_hash']);
            $t->dropIndex(['session_token_id']);
            $t->dropColumn(['token_hash', 'session_token_id']);
        });
    }
};
