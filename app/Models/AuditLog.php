<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToChurch;

    //

    public function user() { return $this->belongsTo(User::class); }
}