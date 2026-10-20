<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaSupportTicket extends Model {
 protected $guarded=['id'];
 protected function casts(): array { return ['resolved_at'=>'datetime','closed_at'=>'datetime','last_reply_at'=>'datetime']; }
 public function creator(){return $this->belongsTo(User::class,'user_id');}
 public function assignee(){return $this->belongsTo(User::class,'assigned_to');}
 public function business(){return $this->belongsTo(Business::class);}
 public function messages(){return $this->hasMany(GrowyaSupportMessage::class,'ticket_id');}
 public function events(){return $this->hasMany(GrowyaSupportEvent::class,'ticket_id');}
}
