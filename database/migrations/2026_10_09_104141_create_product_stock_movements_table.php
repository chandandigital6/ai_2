<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('businesses')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('movement_type', 30);

            $table->integer('quantity_change');

            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');

            $table->string('reason', 255)->nullable();

            $table->timestamps();

            $table->index([
                'business_id',
                'created_at',
            ]);

            $table->index([
                'product_id',
                'product_variant_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_movements');
    }
};
