<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessEnquiryNote extends Model
{
    protected $fillable = ['business_enquiry_id', 'created_by', 'note', 'follow_up_at', 'completed_at'];
    protected function casts(): array
    {
        return ['follow_up_at' => 'datetime', 'completed_at' => 'datetime'];
    }
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(BusinessEnquiry::class, 'business_enquiry_id');
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
