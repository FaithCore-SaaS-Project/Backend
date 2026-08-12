<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTopupRequest extends Model
{
    protected $fillable = [
        'church_id',
        'amount',
        'price',
        'status',
    ];

    public function church()
    {
        return $this->belongsTo(Church::class, 'church_id');
    }
}
