<?php

namespace App\Models;

use App\Services\GrowyaPaidPromotionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    public function scopeSponsoredFirst(Builder $query): Builder
    {
        return app(GrowyaPaidPromotionService::class)->prioritize($query, 'business');
    }

    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_sponsored' => 'boolean',
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
            'joined_at',
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
            ServiceEnquiry::class,
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
            Appointment::class
        );
    }

    public function verificationLogs()
    {
        return $this->hasMany(
            BusinessVerificationLog::class,
            'business_id'
        );
    }
}
