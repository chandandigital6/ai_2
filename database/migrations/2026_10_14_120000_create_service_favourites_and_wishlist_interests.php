<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['service_id', 'user_id']);
        });
        Schema::create('wishlist_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('item_type', 12);
            $table->unsignedBigInteger('item_id');
            $table->boolean('contact_consent')->default(false);
            $table->string('status', 24)->default('new');
            $table->text('seller_note')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'item_type', 'item_id'], 'wish_interest_unique');
            $table->index(['business_id', 'status', 'created_at']);
            $table->index(['item_type', 'item_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('wishlist_interests');
        Schema::dropIfExists('service_favourites');
    }
};
