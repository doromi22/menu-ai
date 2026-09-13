<?php

namespace App\Http\Responders;

use App\Http\Responders\Payloads\ImagePayload;
use App\Models\Image;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class UserImagesResponder
{
    /**
     * @param  Collection<int, Image>  $images
     * @param  array<int, bool>  $reviewRequired  keyed by image id
     */
    public function respond(Collection $images, array $reviewRequired): JsonResponse
    {
        return response()->json([
            'images' => $images->map(fn (Image $image) => [
                'id' => $image->id,
                'status' => $image->status,
                'original_url' => ImagePayload::originalUrl($image),
                'processed_url' => ImagePayload::processedUrl($image),
                'review_required' => $reviewRequired[$image->id] ?? false,
                'created_at' => $image->created_at->format('Y/m/d H:i'),
            ]),
        ]);
    }

    public function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => '未ログイン'], 401);
    }
}
