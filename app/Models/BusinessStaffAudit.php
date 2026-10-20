<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessStaffAudit extends Model
{
    protected $fillable = ['business_id','actor_user_id','subject_user_id','action','before_data','after_data'];
    protected function casts(): array { return ['before_data' => 'array', 'after_data' => 'array']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(User::class, 'subject_user_id'); }
}
