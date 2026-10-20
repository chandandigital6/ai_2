<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMedia extends Model
{
    protected $table = 'ai_media';
    protected $fillable = [
        'ai_generation_id',
        'business_id',
        'media_type',
        'file_url',
        'thumbnail',
        'width',
        'height',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function ai_generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
