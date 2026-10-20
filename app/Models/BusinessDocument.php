<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessDocument extends Model
{
    protected $table = 'business_documents';
    protected $fillable = [
        'business_id',
        'document_type',
        'document_number',
        'file_url',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function verified(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
