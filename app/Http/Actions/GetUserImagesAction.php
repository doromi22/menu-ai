<?php

namespace App\Http\Actions;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetUserImagesAction extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['message' => '未ログイン'], 401);
        }

        $images = Image::where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($img) {
                return [
                    'id' => $img->id,
                    'status' => $img->status,
                    'original_url' => asset('storage/' . $img->original_path),
                    'processed_url' => $img->processed_path ? asset('storage/' . $img->processed_path) : null,
                    'created_at' => $img->created_at->format('Y/m/d H:i'),
                ];
            });

        return response()->json(['images' => $images]);
    }
}