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
        'status'
    ];

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
