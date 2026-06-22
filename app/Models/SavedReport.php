<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class SavedReport extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'name',
        'type',
        'category',
        'date_range'
    ];

    public function church()
    {
        return $this->belongsTo(Church::class);
    }
}
