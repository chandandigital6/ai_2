<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'attributes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                // NULL preserves platform master data. Existing IDs and associations stay intact.
                $table->foreignId('business_id')->nullable()->constrained('businesses')->restrictOnDelete();
                $table->index(['business_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        foreach (['attributes', 'categories'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropIndex(['business_id', 'name']);
                $table->dropColumn('business_id');
            });
        }
    }
};
