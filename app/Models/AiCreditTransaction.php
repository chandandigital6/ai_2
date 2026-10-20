<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiCreditTransaction extends Model
{
    protected $table = 'ai_credit_transactions';
    protected $fillable = [
        'business_id',
        'ai_generation_id',
        'type',
        'credits',
        'balance_after',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function ai_generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }
}
