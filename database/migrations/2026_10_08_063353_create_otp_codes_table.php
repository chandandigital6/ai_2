<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 20)->index();

            // Plain OTP की जगह Hash Store करेंगे।
            $table->string('otp');

            $table->string('purpose', 30)
                ->default('login');

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();

            $table->unsignedTinyInteger('attempts')
                ->default(0);

            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
