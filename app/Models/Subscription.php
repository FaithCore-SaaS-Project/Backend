<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'plan_id',
        'status',
        'start_date',
        'end_date',
        'amount',
        'billing_cycle'
    ];

    public function plan() { return $this->belongsTo(Plan::class); }
}