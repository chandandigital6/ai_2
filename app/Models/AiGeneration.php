<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGeneration extends Model
{
    protected $table = 'ai_generations';

    protected $fillable = [
        'ai_plan_request_id',
        'error_code',
        'business_id',
        'user_id',
        'generation_type',
        'provider',
        'model',
        'prompt',
        'input_json',
        'output_json',
        'language',
        'credits_used',
        'cost',
        'status',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'input_json' => 'array',
            'output_json' => 'array',
            'credits_used' => 'integer',
            'cost' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ai_media(): HasMany
    {
        return $this->hasMany(AiMedia::class, 'ai_generation_id');
    }

    public function ai_credit_transactions(): HasMany
    {
        return $this->hasMany(AiCreditTransaction::class, 'ai_generation_id');
    }
}
