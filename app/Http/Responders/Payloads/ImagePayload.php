<?php

namespace App\Http\Responders\Payloads;

use App\Models\Image;

final class ImagePayload
{
    public static function originalUrl(Image $image): string
    {
        return asset('storage/'.$image->original_path);
    }

    public static function processedUrl(Image $image): ?string
    {
        return $image->processed_path ? asset('storage/'.$image->processed_path) : null;
    }
}
