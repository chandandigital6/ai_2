<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id'];


public function parent()
{
    return $this->belongsTo(
        self::class,
        'parent_id'
    );
}

public function children()
{
    return $this->hasMany(
        self::class,
        'parent_id'
    );
}

public function businesses()
{
    return $this->hasMany(Business::class);
}

public function products()
{
    return $this->hasMany(Product::class);
}

public function services()
{
    return $this->hasMany(Service::class);
}


}
