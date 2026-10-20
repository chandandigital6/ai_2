<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->nullable()->index();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->string('logo')->nullable();
            $table->string('cover')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();

            $table->text('address')->nullable();
            $table->string('pincode', 12)->nullable();

            $table->foreignId('country_id')->nullable()->index();
            $table->foreignId('state_id')->nullable()->index();
            $table->foreignId('city_id')->nullable()->index();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('gst_number', 30)->nullable();
            $table->string('upi_id')->nullable();

            $table->boolean('cod_enabled')->default(false);
            $table->boolean('upi_enabled')->default(false);

            $table->string('verification_status', 30)
                ->default('pending')->index();

            $table->string('status', 20)
                ->default('active')->index();

            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('followers_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
