<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'name',
        'type',
        'recipient',
        'recipient_email',
        'recipient_phone',
        'issued_date',
        'issued_by',
        'status',
        'pdf_file'
    ];
}