<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('previous_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamps();
            $table->index(['business_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_ownership_transfers');
    }
};
