<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WishlistInterest extends Model
{
    protected $fillable = ['business_id','user_id','item_type','item_id','contact_consent','status','seller_note','follow_up_at','updated_by'];
    protected function casts(): array { return ['contact_consent'=>'boolean','follow_up_at'=>'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function business() { return $this->belongsTo(Business::class); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
}
