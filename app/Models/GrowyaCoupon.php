<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaCoupon extends Model {
 protected $fillable=['business_id','code','discount_type','discount_value','minimum_subtotal','maximum_discount','usage_limit','per_user_limit','used_count','starts_at','ends_at','is_active'];
 protected function casts(): array {return ['starts_at'=>'datetime','ends_at'=>'datetime','is_active'=>'boolean'];}
 public function redemptions(){return $this->hasMany(GrowyaCouponRedemption::class,'coupon_id');}
}
