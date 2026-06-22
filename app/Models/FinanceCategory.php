<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceCategory extends Model
{
    use HasFactory, BelongsToChurch;

    protected $table = 'finance_categories';

    protected $fillable = [
        'church_id',
        'name',
        'type',
        'description',
        'status',
        'created_on',
        'created_by'
    ];
}
