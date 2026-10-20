<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaBusinessClaimDocument extends Model {
 protected $guarded=['id'];
 public function claim(){return $this->belongsTo(GrowyaBusinessClaim::class,'claim_id');}
}
