<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessVerificationLog extends Model
{
    protected $table = 'business_verification_logs';

    protected $fillable = [
        'business_id',
        'performed_by',
        'old_status',
        'new_status',
        'reason',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by'
        );
    }
}
