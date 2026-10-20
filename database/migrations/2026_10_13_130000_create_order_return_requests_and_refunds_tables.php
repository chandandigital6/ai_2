<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20); // cancellation | return
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, refunded, resolved
            $table->text('reason');
            $table->text('seller_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('order_refund_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->unique()->constrained('order_return_requests')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30); // manual_upi, bank_transfer, other
            $table->string('reference', 120)->unique();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_refund_records');
        Schema::dropIfExists('order_return_requests');
    }
};
