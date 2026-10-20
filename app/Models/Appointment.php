<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $table = 'appointments';

    protected $fillable = [
        'business_id',
        'service_id',
        'customer_id',
        'service_enquiry_id',
        'customer_name',
        'customer_phone',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'string',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'customer_id'
        );
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(
            ServiceEnquiry::class,
            'service_enquiry_id'
        );
    }
}
