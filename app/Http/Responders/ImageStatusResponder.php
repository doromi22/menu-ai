<?php

namespace App\Http\Responders;

use App\Http\Responders\Payloads\ImagePayload;
use App\Models\Image;
use Illuminate\Http\JsonResponse;

class ImageStatusResponder
{
    public function respond(Image $image, bool $reviewRequired, ?string $message): JsonResponse
    {
        return response()->json([
            'id' => $image->id,
            'status' => $image->status,
            'original_url' => ImagePayload::originalUrl($image),
            'processed_url' => ImagePayload::processedUrl($image),
            'review_required' => $reviewRequired,
            'message' => $message,
        ]);
    }

    public function forbidden(): JsonResponse
    {
        return response()->json(['message' => '権限がありません。'], 403);
    }
}
