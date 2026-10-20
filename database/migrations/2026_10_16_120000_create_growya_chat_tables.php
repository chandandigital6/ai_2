<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('growya_chat_threads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $t->string('context_type', 15)->default('general');
            $t->unsignedBigInteger('context_id')->default(0);
            $t->timestamp('last_message_at')->nullable();
            $t->timestamps();
            $t->unique(['business_id', 'customer_id', 'context_type', 'context_id'], 'growya_chat_unique_thread');
            $t->index(['business_id', 'last_message_at']);
            $t->index(['customer_id', 'last_message_at']);
        });
        Schema::create('growya_chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('thread_id')->constrained('growya_chat_threads')->cascadeOnDelete();
            $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $t->text('body')->nullable();
            $t->string('file_path')->nullable();
            $t->string('original_filename', 180)->nullable();
            $t->string('file_mime', 100)->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['thread_id', 'id']);
            $t->index(['sender_id', 'read_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('growya_chat_messages');
        Schema::dropIfExists('growya_chat_threads');
    }
};
