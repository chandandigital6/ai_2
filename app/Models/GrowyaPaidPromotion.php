<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaPaidPromotion extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['starts_at'=>'datetime','ends_at'=>'datetime','reviewed_at'=>'datetime'];}
 public function package(){return $this->belongsTo(GrowyaPromotionPackage::class,'package_id');}
 public function business(){return $this->belongsTo(Business::class);}
 public function product(){return $this->belongsTo(Product::class);}
 public function requester(){return $this->belongsTo(User::class,'requested_by');}
 public function scopeLive($query){return $query->where('status','active')->where('starts_at','<=',now())->where('ends_at','>',now());}
}
