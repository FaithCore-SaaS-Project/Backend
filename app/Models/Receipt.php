<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToChurch;

class Receipt extends Model
{
    use HasFactory, BelongsToChurch;

    protected $fillable = [
        'church_id',
        'receipt_no',
        'receipt_date',
        'member_name',
        'member_email',
        'member_phone',
        'category',
        'amount',
        'method',
        'status',
        'received_by',
        'description'
    ];

    // The church() relationship and scoping are automatically handled by the BelongsToChurch trait
}
