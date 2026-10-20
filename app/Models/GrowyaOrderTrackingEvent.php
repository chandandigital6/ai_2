<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaOrderTrackingEvent extends Model {
 protected $table='growya_order_tracking_events';
 protected $fillable=['order_id','performed_by','stage','note'];
 public function actor(){return $this->belongsTo(User::class,'performed_by');}
 public function order(){return $this->belongsTo(Order::class,'order_id');}
}
