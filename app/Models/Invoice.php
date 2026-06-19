<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class Invoice extends Model
{
    use BelongsToChurch;

    protected $guarded = [];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
