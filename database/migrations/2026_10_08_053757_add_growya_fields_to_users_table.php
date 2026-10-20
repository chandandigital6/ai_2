<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();

            $table->string('user_type', 30)
                ->default('customer')->index();

            $table->string('status', 20)
                ->default('active')->index();

            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'user_type',
                'status',
                'last_login_at',
            ]);
        });
    }
};
