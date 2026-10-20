<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaReferral extends Model {
 protected $guarded=['id'];
 protected function casts():array {return ['customer_rewarded_at'=>'datetime','business_rewarded_at'=>'datetime'];}
 public function referrer(){return $this->belongsTo(User::class,'referrer_id');}
 public function referredUser(){return $this->belongsTo(User::class,'referred_user_id');}
}
