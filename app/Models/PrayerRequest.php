<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrayerRequest extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'member_id',
        'title',
        'description',
        'status',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
