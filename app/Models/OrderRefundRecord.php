<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRefundRecord extends Model
{
    protected $fillable = ['request_id','order_id','amount','method','reference','note','recorded_by','refunded_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','refunded_at'=>'datetime']; }
    public function request(): BelongsTo { return $this->belongsTo(OrderReturnRequest::class,'request_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class,'recorded_by'); }
}
