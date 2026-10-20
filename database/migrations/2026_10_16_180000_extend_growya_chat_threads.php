<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('growya_chat_threads', function (Blueprint $t) {
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('customer_blocked')->default(false);
            $t->boolean('business_blocked')->default(false);
            $t->timestamp('customer_read_at')->nullable();
            $t->timestamp('business_read_at')->nullable();
            $t->string('followup_status', 24)->default('new');
            $t->timestamp('followup_at')->nullable();
            $t->index(['business_id', 'assigned_to']);
        });
        Schema::create('growya_chat_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('growya_chat_threads')->cascadeOnDelete();
            $t->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $t->text('reason');
            $t->string('status', 20)->default('pending');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('admin_note')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
        Schema::create('growya_chat_activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('growya_chat_threads')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 40);
            $t->text('detail')->nullable();
            $t->timestamps();
            $t->index(['thread_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('growya_chat_activity_logs');
        Schema::dropIfExists('growya_chat_reports');
        Schema::table('growya_chat_threads', function (Blueprint $t) {
            $t->dropIndex(['business_id', 'assigned_to']);
            $t->dropConstrainedForeignId('assigned_to');
            $t->dropColumn(['customer_blocked', 'business_blocked', 'customer_read_at', 'business_read_at', 'followup_status', 'followup_at']);
        });
    }
};
