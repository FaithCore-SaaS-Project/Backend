<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'title',
        'letter_type',
        'recipient',
        'recipient_email',
        'recipient_phone',
        'issue_date',
        'status',
        'sent_by',
        'content',
        'pdf_file'
    ];
}
