<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrowyaChatThread extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['last_message_at' => 'datetime', 'customer_read_at' => 'datetime', 'business_read_at' => 'datetime', 'followup_at' => 'datetime', 'customer_blocked' => 'boolean', 'business_blocked' => 'boolean']; }
    public function business() { return $this->belongsTo(Business::class); }
    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function messages() { return $this->hasMany(GrowyaChatMessage::class, 'thread_id'); }
    public function latestMessage() { return $this->hasOne(GrowyaChatMessage::class, 'thread_id')->latestOfMany(); }
}
