<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('growya_order_shipments',function(Blueprint $t){
   $t->id(); $t->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
   $t->string('stage',30)->default('placed');
   $t->string('courier_name',120)->nullable(); $t->string('tracking_number',120)->nullable();
   $t->string('tracking_url',2048)->nullable(); $t->date('estimated_delivery_date')->nullable();
   $t->timestamps();
  });
  Schema::create('growya_order_tracking_events',function(Blueprint $t){
   $t->id(); $t->foreignId('order_id')->constrained('orders')->restrictOnDelete();
   $t->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
   $t->string('stage',30); $t->text('note')->nullable(); $t->timestamps();
   $t->index(['order_id','created_at']);
  });
 }
 public function down():void {Schema::dropIfExists('growya_order_tracking_events'); Schema::dropIfExists('growya_order_shipments');}
};
