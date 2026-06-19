<?php

namespace App\Models;

use App\Models\Traits\BelongsToChurch;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use BelongsToChurch;

    //

    public function category() { return $this->belongsTo(DocumentCategory::class, 'category_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}