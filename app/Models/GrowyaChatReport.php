<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GrowyaChatReport extends Model { protected $guarded=['id']; protected function casts():array{return ['reviewed_at'=>'datetime'];} public function thread(){return $this->belongsTo(GrowyaChatThread::class,'thread_id');} public function reporter(){return $this->belongsTo(User::class,'reported_by');} }
