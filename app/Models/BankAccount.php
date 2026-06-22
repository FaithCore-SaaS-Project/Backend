<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory, BelongsToChurch;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'church_id',
        'bank_name',
        'account_name',
        'account_number',
        'account_type',
        'balance',
        'ledger_balance',
        'status',
        'branch',
        'currency',
        'last_statement_date',
        'created_on',
        'created_by'
    ];
}
