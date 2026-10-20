<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Business extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'cod_enabled' => 'boolean',
            'upi_enabled' => 'boolean',
            'views_count' => 'integer',
            'followers_count' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function businessUsers(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'business_users'
        )->withPivot([
            'id',
            'role',
            'is_active',
            'joined_at'
        ])->withTimestamps();
    }



public function hours()
{
    return $this->hasMany(BusinessHour::class);
}

public function socialLinks()
{
    return $this->hasMany(BusinessSocialLink::class);
}

public function category()
{
    return $this->belongsTo(Category::class);
}

public function country()
{
    return $this->belongsTo(Country::class);
}

public function state()
{
    return $this->belongsTo(State::class);
}

public function city()
{
    return $this->belongsTo(City::class);
}





public function products()
{
    return $this->hasMany(Product::class);
}

public function services()
{
    return $this->hasMany(Service::class);
}

public function serviceEnquiries()
{
    return $this->hasMany(
        \App\Models\ServiceEnquiry::class,
        'business_id'
    );
}

public function orders()
{
    return $this->hasMany(Order::class);
}

public function followers()
{
    return $this->hasMany(BusinessFollower::class);
}

public function reviews()
{
    return $this->hasMany(Review::class);
}

public function enquiries()
{
    return $this->hasMany(BusinessEnquiry::class);
}




public function gallery()
{
    return $this->hasMany(BusinessGallery::class);
}

public function subscriptions()
{
    return $this->hasMany(Subscription::class);
}

public function aiGenerations()
{
    return $this->hasMany(AiGeneration::class);
}

public function socialAccounts()
{
    return $this->hasMany(SocialAccount::class);
}

public function socialPosts()
{
    return $this->hasMany(SocialPost::class);
}


public function appointments()
{
    return $this->hasMany(
        \App\Models\Appointment::class
    );
}

public function verificationLogs()
{
    return $this->hasMany(
        \App\Models\BusinessVerificationLog::class,
        'business_id'
    );
}

}

