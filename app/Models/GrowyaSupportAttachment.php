<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaSupportAttachment extends Model {protected $guarded=['id'];public function message(){return $this->belongsTo(GrowyaSupportMessage::class,'message_id');}}
