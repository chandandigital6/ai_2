<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();

            $table->foreignId('service_enquiry_id')
                ->nullable()
                ->constrained('service_enquiries')
                ->nullOnDelete();

            $table->index([
                'business_id',
                'appointment_date',
                'status',
            ], 'appointments_business_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_business_date_status_idx');
            $table->dropConstrainedForeignId('service_enquiry_id');
            $table->dropColumn([
                'customer_name',
                'customer_phone',
            ]);
        });
    }
};
