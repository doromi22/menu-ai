<?php

namespace App\Http\Responders;

use App\Domain\Image\ProcessingOutcome;
use App\Http\Responders\Payloads\ImagePayload;
use App\Models\Image;
use Illuminate\Http\JsonResponse;

class ImageStatusResponder
{
    public function respond(Image $image): JsonResponse
    {
        return response()->json([
            'id' => $image->id,
            'status' => $image->status,
            'original_url' => ImagePayload::originalUrl($image),
            'processed_url' => ImagePayload::processedUrl($image),
            'review_required' => ProcessingOutcome::requiresReview([
                'segmentation_status' => $image->segmentation_status,
                'validator_status' => $image->validator_status,
            ]),
            'message' => ProcessingOutcome::messageForImage($image),
        ]);
    }

    public function forbidden(): JsonResponse
    {
        return response()->json(['message' => '権限がありません。'], 403);
    }
}
