<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class EventRegistration extends Model
{
    use BelongsToChurch;

    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
