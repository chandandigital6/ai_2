<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaBusinessClaim extends Model {
 protected $guarded=['id'];
 protected function casts(): array { return ['reviewed_at'=>'datetime']; }
 public function business(){return $this->belongsTo(Business::class);}
 public function claimant(){return $this->belongsTo(User::class,'claimant_id');}
 public function reviewer(){return $this->belongsTo(User::class,'reviewed_by');}
 public function documents(){return $this->hasMany(GrowyaBusinessClaimDocument::class,'claim_id');}
 public function events(){return $this->hasMany(GrowyaBusinessClaimEvent::class,'claim_id');}
}
