<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaOrderShipment extends Model {
 protected $table='growya_order_shipments';
 protected $fillable=['order_id','stage','courier_name','tracking_number','tracking_url','estimated_delivery_date'];
 protected function casts():array{return ['estimated_delivery_date'=>'date'];}
 public function order(){return $this->belongsTo(Order::class,'order_id');}
}
