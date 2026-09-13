<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageProcessingReason extends Model
{
    protected $fillable = [
        'image_id',
        'stage',
        'reason_code',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }
}
