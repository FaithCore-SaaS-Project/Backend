<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use BelongsToChurch;

    use HasFactory;

    protected $fillable = [
        'church_id',
        'event_name',
        'event_date',
        'venue',
        'description'
    ];

    public function members()
    {
        return $this->belongsToMany(
            Member::class,
            'event_registrations'
        );
    }
}
