<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_user_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_user_id')
                ->constrained('business_users')
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'business_user_id',
                'permission_id',
            ], 'business_user_permission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_user_permissions');
    }
};
