<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class DepartmentMember extends Model
{
    use BelongsToChurch;

    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
