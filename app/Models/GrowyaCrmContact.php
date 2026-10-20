<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaCrmContact extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['next_follow_up_at'=>'datetime']; }
    public function activities() { return $this->hasMany(GrowyaCrmActivity::class, 'contact_id')->latest(); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
}
