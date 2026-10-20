<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Festival extends Model
{
    protected $table = 'festivals';
    protected $fillable = [
        'name',
        'festival_date',
        'region_filters',
        'default_caption',
        'default_prompt',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'festival_date' => 'date',
            'region_filters' => 'array',
        ];
    }
}
