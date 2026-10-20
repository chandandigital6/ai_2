<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $table = 'attributes';

    protected $fillable = [
        'business_id',
        'name',
        'slug',
    ];

    public function values()
    {
        return $this->hasMany(
            AttributeValue::class,
            'attribute_id'
        );
    }

    public function attribute_values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id');
    }
}
