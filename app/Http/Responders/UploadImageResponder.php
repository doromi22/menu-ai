<?php

namespace App\Http\Responders;

use App\Http\Responders\Payloads\ImagePayload;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UploadImageResponder
{
    public function uploaded(Image $image, User $user): JsonResponse
    {
        return response()->json([
            'message' => '画像のアップロードに成功しました。',
            'data' => [
                'id' => $image->id,
                'status' => $image->status,
                'original_url' => ImagePayload::originalUrl($image),
                'remaining_credits' => $user->credits,
            ],
        ], 201);
    }

    public function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => 'ログインが必要です。'], 401);
    }

    public function insufficientCredits(): JsonResponse
    {
        return response()->json(['message' => 'クレジットが不足しています。プランの購入をご検討ください。'], 403);
    }
}
