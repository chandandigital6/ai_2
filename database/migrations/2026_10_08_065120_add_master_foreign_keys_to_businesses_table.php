<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();

            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->restrictOnDelete();

            $table->foreign('state_id')
                ->references('id')
                ->on('states')
                ->restrictOnDelete();

            $table->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
        });
    }
};
