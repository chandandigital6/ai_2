<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBrandProfile extends Model
{
    protected $table = 'ai_brand_profiles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }
}
