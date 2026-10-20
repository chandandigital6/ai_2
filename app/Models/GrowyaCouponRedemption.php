<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaCouponRedemption extends Model {
 public $timestamps=false;
 protected $fillable=['coupon_id','business_id','user_id','order_id','discount_amount'];
}
