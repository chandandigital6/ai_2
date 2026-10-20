<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPlan extends Model
{
    protected $table = 'ai_plans';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'credits' => 'integer', 'price_paise' => 'integer', 'valid_days' => 'integer', 'text_credits' => 'integer', 'image_credits' => 'integer', 'max_prompts' => 'integer', 'max_references' => 'integer'];
    }
}
