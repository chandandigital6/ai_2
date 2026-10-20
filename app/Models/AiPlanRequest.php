<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPlanRequest extends Model
{
    protected $table = 'ai_plan_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['plan_snapshot' => 'array', 'used_credits' => 'integer', 'approved_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
