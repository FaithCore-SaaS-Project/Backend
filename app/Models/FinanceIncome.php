<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class FinanceIncome extends Model
{
    use BelongsToChurch;

    use HasFactory;

    protected $table = 'finance_income';
    protected $fillable = [
        'church_id',
        'category_id',
        'amount',
        'income_date',
        'description'
    ];

    public function category() { return $this->belongsTo(FinanceCategory::class, 'category_id'); }
}