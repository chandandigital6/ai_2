<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            $table->json('specifications')->nullable();

            $table->index(['business_id', 'is_featured']);
            $table->index(['business_id', 'is_trending']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex([
                'business_id',
                'is_featured',
            ]);

            $table->dropIndex([
                'business_id',
                'is_trending',
            ]);

            $table->dropColumn([
                'is_featured',
                'is_trending',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'specifications',
            ]);
        });
    }
};
