<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class Setting extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'key',
        'value',
        'group'
    ];

    public function church()
    {
        return $this->belongsTo(Church::class);
    }
}
