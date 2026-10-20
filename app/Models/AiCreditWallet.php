<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiCreditWallet extends Model
{
    protected $table = 'ai_credit_wallets';
    protected $fillable = [
        'business_id',
        'total_credits',
        'used_credits',
    ];

    protected function casts(): array
    {
        return [
            'total_credits' => 'integer',
            'used_credits' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
