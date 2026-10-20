<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {

            $table->string('featured_image')
                ->nullable();

            $table->unsignedInteger('enquiries_count')
                ->default(0);
        });

        Schema::table('service_images', function (Blueprint $table) {

            $table->string('alt_text')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_images', function (Blueprint $table) {
            $table->dropColumn('alt_text');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'featured_image',
                'enquiries_count',
            ]);
        });
    }
};
