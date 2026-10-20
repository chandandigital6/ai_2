<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_support_tickets', function(Blueprint $t) {
   $t->id(); $t->string('ticket_number',32)->unique();
   $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
   $t->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
   $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
   $t->string('subject',200); $t->string('category',35); $t->string('priority',15)->default('medium');
   $t->string('status',25)->default('open'); $t->timestamp('resolved_at')->nullable();
   $t->timestamp('closed_at')->nullable(); $t->timestamp('last_reply_at')->nullable();
   $t->timestamps(); $t->index(['status','priority','created_at']); $t->index(['user_id','created_at']);
  });
  Schema::create('growya_support_messages',function(Blueprint $t){
   $t->id(); $t->foreignId('ticket_id')->constrained('growya_support_tickets')->cascadeOnDelete();
   $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
   $t->text('message'); $t->boolean('is_internal')->default(false); $t->timestamps();
  });
  Schema::create('growya_support_attachments',function(Blueprint $t){
   $t->id(); $t->foreignId('message_id')->constrained('growya_support_messages')->cascadeOnDelete();
   $t->string('disk',30)->default('local'); $t->string('path'); $t->string('original_name',255);
   $t->string('mime_type',120)->nullable(); $t->unsignedBigInteger('size'); $t->timestamps();
  });
  Schema::create('growya_support_events',function(Blueprint $t){
   $t->id(); $t->foreignId('ticket_id')->constrained('growya_support_tickets')->cascadeOnDelete();
   $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
   $t->string('event',40); $t->string('old_value',190)->nullable(); $t->string('new_value',190)->nullable(); $t->timestamps();
  });
 }
 public function down(): void {
  Schema::dropIfExists('growya_support_events'); Schema::dropIfExists('growya_support_attachments');
  Schema::dropIfExists('growya_support_messages'); Schema::dropIfExists('growya_support_tickets');
 }
};
