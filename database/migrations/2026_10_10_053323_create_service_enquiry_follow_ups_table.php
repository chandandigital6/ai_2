<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_enquiry_follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_enquiry_id')
                ->constrained('service_enquiries')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('note');

            $table->dateTime('follow_up_at')->nullable();

            $table->dateTime('completed_at')->nullable();

            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'service_enquiry_id',
                'follow_up_at',
            ], 'service_followup_enquiry_date_idx');

            $table->index([
                'completed_at',
                'follow_up_at',
            ], 'service_followup_pending_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_enquiry_follow_ups');
    }
};
