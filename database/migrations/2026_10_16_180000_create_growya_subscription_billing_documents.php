<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('growya_subscription_billing_documents')) {
            throw new RuntimeException('Billing document table already exists. Reconcile its schema before marking this migration as applied; existing invoices were preserved.');
        }

        Schema::create('growya_subscription_billing_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->nullable()->unique('gsbd_doc_num_unique');

            // Short custom index & foreign key names (under 64 chars)
            $table->foreignId('subscription_payment_id')
                ->unique('gsbd_sub_pay_id_unique')
                ->constrained('subscription_payments', 'id', 'gsbd_sub_pay_id_fk')
                ->restrictOnDelete();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions', 'id', 'gsbd_sub_id_fk')
                ->restrictOnDelete();

            $table->foreignId('business_id')
                ->constrained('businesses', 'id', 'gsbd_bus_id_fk')
                ->restrictOnDelete();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users', 'id', 'gsbd_issued_by_fk')
                ->nullOnDelete();

            $table->string('document_type', 30); // manual_receipt | complimentary_statement
            $table->string('payment_status_snapshot', 30);
            $table->string('payment_method_snapshot', 50)->nullable();
            $table->string('payment_reference_snapshot', 150)->nullable();
            $table->string('payment_currency', 10)->default('INR');
            $table->decimal('amount', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0); // no tax configured: always zero
            $table->string('issuer_name', 200);
            $table->text('issuer_address')->nullable();
            $table->string('issuer_gstin', 30)->nullable();
            $table->string('business_name', 200);
            $table->text('business_address')->nullable();
            $table->string('business_gstin', 30)->nullable();
            $table->string('plan_name', 200);
            $table->string('billing_cycle', 30)->nullable();
            $table->timestamp('service_starts_at')->nullable();
            $table->timestamp('service_ends_at')->nullable();
            $table->timestamp('recorded_payment_at')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();

            $table->index(['business_id', 'issued_at'], 'gsbd_bus_issued_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growya_subscription_billing_documents');
    }
};
