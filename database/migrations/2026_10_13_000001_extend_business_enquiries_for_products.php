<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('business_enquiries', function(Blueprint $table){
            $table->unsignedInteger('quantity')->nullable();
        });
        Schema::create('business_enquiry_notes', function(Blueprint $table){
            $table->id();
            $table->foreignId('business_enquiry_id')->constrained('business_enquiries')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->dateTime('follow_up_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['business_enquiry_id','follow_up_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('business_enquiry_notes');
        Schema::table('business_enquiries',fn(Blueprint $table)=>$table->dropColumn('quantity'));
    }
};
