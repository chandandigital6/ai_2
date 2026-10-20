<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('growya_kyc_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('pending')->index();
            $t->string('legal_name', 255);
            $t->string('entity_type', 40);
            $t->text('declaration')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('review_reason')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->date('reverify_by')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });
        Schema::create('growya_kyc_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('growya_kyc_submissions')->cascadeOnDelete();
            $t->string('document_type', 40);
            $t->string('file_path');
            $t->string('original_name');
            $t->string('mime_type', 100);
            $t->unsignedBigInteger('file_size');
            $t->date('expires_on')->nullable();
            $t->timestamps();
            $t->unique(['submission_id', 'document_type'], 'growya_kyc_doc_unique');
        });
        Schema::create('growya_kyc_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('growya_kyc_submissions')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 50);
            $t->text('reason')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('growya_kyc_events');
        Schema::dropIfExists('growya_kyc_documents');
        Schema::dropIfExists('growya_kyc_submissions');
    }
};
