<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToChurch;

    //

    public function plan() { return $this->belongsTo(Plan::class); }
}