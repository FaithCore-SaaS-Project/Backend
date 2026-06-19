<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $guarded = [];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
