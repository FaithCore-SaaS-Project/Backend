<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchService extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'name',
        'start_time',
        'end_time',
        'day_of_week'
    ];

    public function church()
    {
        return $this->belongsTo(Church::class);
    }
}
