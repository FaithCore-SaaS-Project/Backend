<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'department_name',
        'leader_id',
        'description'
    ];

    public function members()
    {
        return $this->belongsToMany(
            Member::class,
            'department_members'
        );
    }
}
