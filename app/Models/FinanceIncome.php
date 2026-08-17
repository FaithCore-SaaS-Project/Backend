<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceIncome extends Model
{
    use HasFactory, BelongsToChurch;

    protected $table = 'finance_income';

    protected $fillable = [
        'church_id',
        'category',
        'amount',
        'income_date',
        'method',
        'receipt',
        'description',
        'member_id',
        'recorded_by'
    ];
}