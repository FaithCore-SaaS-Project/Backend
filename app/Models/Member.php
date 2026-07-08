<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'family_id',
        'member_no',
        'first_name',
        'last_name',
        'nic',
        'phone',
        'email',
        'gender',
        'dob',
        'address',
        'address_type',
        'permanent_address',
        'postal_address',
        'is_baptized',
        'baptism_church',
        'baptism_partner_name',
        'baptism_certificate',
        'baptism_date',
        'membership_date',
        'occupation',
        'marital_status',
        'marriage_date',
        'marriage_certificate',
        'birth_certificate',
        'photo',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($member) {
            // Sync permanent/postal address to legacy address column for backward compatibility
            if ($member->permanent_address) {
                $member->address = $member->permanent_address;
            } elseif ($member->postal_address) {
                $member->address = $member->postal_address;
            }
        });
    }

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return url(\Illuminate\Support\Facades\Storage::url($this->photo));
        }
        return null;
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_members');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_registrations');
    }
}