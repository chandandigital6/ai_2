<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $table = 'tags';
    protected $fillable = [
        'name',
        'slug',
    ];




public function products()
{
    return $this->belongsToMany(
        Product::class,
        'product_tag',
        'tag_id',
        'product_id'
    )->withTimestamps();
}

    public function product_tag(): HasMany
    {
        return $this->hasMany(ProductTag::class, 'tag_id');
    }
}
