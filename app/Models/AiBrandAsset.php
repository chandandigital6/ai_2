<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiBrandAsset extends Model
{
    use SoftDeletes;

    protected $table = 'ai_brand_assets';

    protected $guarded = ['id'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return [];
    }
}
