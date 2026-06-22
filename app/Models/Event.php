<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'event_name',
        'subtitle',
        'type',
        'event_date',
        'event_time',
        'venue',
        'attendees',
        'max_capacity',
        'status',
        'organizer',
        'description',
        'created_on'
    ];

    public function members()
    {
        return $this->belongsToMany(
            Member::class,
            'event_registrations'
        );
    }
}
