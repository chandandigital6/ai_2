<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    protected $table = 'features';
    protected $fillable = [
        'key_name',
        'name',
        'data_type',
    ];

    public function plan_features(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'feature_id');
    }
}
