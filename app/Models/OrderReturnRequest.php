<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderReturnRequest extends Model
{
    protected $fillable = ['order_id','customer_id','type','status','reason','seller_note','reviewed_by','reviewed_at'];
    protected function casts(): array { return ['reviewed_at'=>'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class,'customer_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class,'reviewed_by'); }
    public function refund(): HasOne { return $this->hasOne(OrderRefundRecord::class,'request_id'); }
}
