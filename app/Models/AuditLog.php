<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'user_id',
        'event',
        'ip_address',
        'user_agent'
    ];

    public function user() { return $this->belongsTo(User::class); }
}