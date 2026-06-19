<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use BelongsToChurch;

    //

    public function member() { return $this->belongsTo(Member::class); }
}