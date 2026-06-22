<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceExpense extends Model
{
    use HasFactory, BelongsToChurch;

    protected $table = 'finance_expenses';

    protected $fillable = [
        'church_id',
        'category',
        'amount',
        'expense_date',
        'method',
        'receipt',
        'description'
    ];
}