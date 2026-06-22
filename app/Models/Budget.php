<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory, BelongsToChurch;

    protected $table = 'budgets';

    protected $fillable = [
        'church_id',
        'name',
        'type',
        'budget_amount',
        'spent_amount',
        'period_start',
        'period_end',
        'status',
        'description',
        'created_on'
    ];
}
