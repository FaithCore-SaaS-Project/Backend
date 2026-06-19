<?php

namespace App\Models\Traits;

use App\Models\Church;
use App\Models\Scopes\ChurchScope;

trait BelongsToChurch
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new ChurchScope);

        // Automatically assign church_id when creating new records if authenticated
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->church_id && !$model->church_id) {
                $model->church_id = auth()->user()->church_id;
            }
        });
    }

    /**
     * Get the church that owns the model.
     */
    public function church()
    {
        return $this->belongsTo(Church::class);
    }
}
