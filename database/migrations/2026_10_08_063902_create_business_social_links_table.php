<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_social_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('platform', 50);
            $table->string('username')->nullable();
            $table->text('url');

            $table->timestamps();

            $table->index(['business_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_social_links');
    }
};
