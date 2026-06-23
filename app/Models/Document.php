<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'category_id',
        'title',
        'file_path',
        'uploaded_by'
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return url(\Illuminate\Support\Facades\Storage::url($this->file_path));
        }
        return null;
    }

    public function category() { return $this->belongsTo(DocumentCategory::class, 'category_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}