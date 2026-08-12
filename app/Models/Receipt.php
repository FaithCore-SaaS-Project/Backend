<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Receipt extends Model
{
    use HasFactory;

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

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->church_id) && auth()->check()) {
                $model->church_id = auth()->user()->church_id;
            }
        });
    }

    public function church()
    {
        return $this->belongsTo(Church::class, 'church_id');
    }
}
