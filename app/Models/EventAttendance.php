<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class EventAttendance extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'event_id',
        'member_id',
        'status',
        'notes'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
