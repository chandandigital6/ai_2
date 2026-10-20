<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEnquiry extends Model
{
    protected $table = 'service_enquiries';

    protected $fillable = [
        'business_id',
        'service_id',
        'user_id',
        'customer_name',
        'phone',
        'preferred_date',
        'preferred_time',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'preferred_time' => 'string',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(
            Business::class,
            'business_id'
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class,
            'service_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }


public function followUps()
{
    return $this->hasMany(
        \App\Models\ServiceEnquiryFollowUp::class,
        'service_enquiry_id'
    );
}


public function appointments()
{
    return $this->hasMany(
        \App\Models\Appointment::class,
        'service_enquiry_id'
    );
}

}
