<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class FinanceExpense extends Model
{
    use BelongsToChurch;

    use HasFactory;

    protected $table = 'finance_expenses';
    protected $fillable = [
        'church_id',
        'category_id',
        'amount',
        'expense_date',
        'description'
    ];

    public function category() { return $this->belongsTo(FinanceCategory::class, 'category_id'); }
}