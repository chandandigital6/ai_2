<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEnquiryFollowUp extends Model
{
    protected $table = 'service_enquiry_follow_ups';

    protected $fillable = [
        'service_enquiry_id',
        'created_by',
        'note',
        'follow_up_at',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(
            ServiceEnquiry::class,
            'service_enquiry_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }

    public function scopePending($query)
    {
        return $query
            ->whereNotNull('follow_up_at')
            ->whereNull('completed_at');
    }

    public function scopeOverdue($query)
    {
        return $query
            ->pending()
            ->where('follow_up_at', '<', now());
    }
}
