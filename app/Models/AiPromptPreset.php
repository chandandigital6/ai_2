<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptPreset extends Model
{
    protected $table = 'ai_prompt_presets';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reference_ids' => 'array'];
    }
}
