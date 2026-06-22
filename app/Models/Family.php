<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'family_name',
        'phone',
        'address'
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
