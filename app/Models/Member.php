<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use BelongsToChurch;

    use HasFactory;

    protected $fillable = [
        'church_id',
        'family_id',
        'member_no',
        'first_name',
        'last_name',
        'phone',
        'email',
        'gender',
        'dob',
        'address',
        'baptism_date',
        'membership_date',
        'occupation',
        'photo',
        'status'
    ];

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

    public function family() { return $this->belongsTo(Family::class); }
    public function certificates() { return $this->hasMany(Certificate::class); }
    public function departments() { return $this->belongsToMany(Department::class, 'department_members'); }
    public function events() { return $this->belongsToMany(Event::class, 'event_registrations'); }
}