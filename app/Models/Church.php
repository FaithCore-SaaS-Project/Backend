<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Church extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_name',
        'registration_no',
        'pastor_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'logo',
        'status',
        'monthly_sms_used',
        'topup_sms_balance',
        'sms_sender_id',
        'cover_image',
        'about',
        'year_established',
        'website',
        'facebook',
        'instagram',
        'youtube',
        'twitter',
        'visibility_settings',
        'currency',
        'timezone',
        'date_format'
    ];

    protected $casts = [
        'visibility_settings' => 'array',
    ];

    public function getCoverImageAttribute($value)
    {
        if (empty($value) || str_contains($value, 'loremflickr') || str_contains($value, 'placeholder') || str_contains($value, 'unsplash') || str_contains($value, 'fake')) {
            return null;
        }
        if (!str_starts_with($value, 'http')) {
            return asset('storage/' . $value);
        }
        return $value;
    }

    public function getLogoAttribute($value)
    {
        if (empty($value) || str_contains($value, 'loremflickr') || str_contains($value, 'placeholder') || str_contains($value, 'unsplash') || str_contains($value, 'fake')) {
            return null;
        }
        if (!str_starts_with($value, 'http')) {
            return asset('storage/' . $value);
        }
        return $value;
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function services()
    {
        return $this->hasMany(ChurchService::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activePlan()
    {
        $sub = $this->subscriptions()->latest()->first();
        if ($sub && in_array($sub->status, ['active', 'trialing']) && $sub->end_date >= date('Y-m-d')) {
            return $sub->plan;
        }
        return null;
    }
}
