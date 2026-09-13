<?php

namespace App\Http\Actions;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckImageStatusAction extends Controller
{
    public function __invoke(Request $request, Image $image): JsonResponse
    {
        // 본인 소유의 이미지인지 확인
        if ($image->user_id !== Auth::id()) {
            return response()->json(['message' => '権限がありません。'], 403);
        }

        return response()->json([
            'id' => $image->id,
            'status' => $image->status,
            'original_url' => asset('storage/' . $image->original_path),
            'processed_url' => $image->processed_path ? asset('storage/' . $image->processed_path) : null,
        ]);
    }
}