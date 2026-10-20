<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $table = 'reports';
    protected $fillable = [
        'user_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'status',
        'handled_by',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'reportable_id' => 'integer',
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function handled(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
