<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderPaymentSubmission extends Model {
    protected $fillable=['order_id','submitted_by','utr','proof_path','amount','status','rejection_reason','reviewed_by','reviewed_at'];
    protected function casts():array{return ['amount'=>'decimal:2','reviewed_at'=>'datetime'];}
    public function order():BelongsTo{return $this->belongsTo(Order::class);}
    public function submittedBy():BelongsTo{return $this->belongsTo(User::class,'submitted_by');}
    public function reviewedBy():BelongsTo{return $this->belongsTo(User::class,'reviewed_by');}
}
