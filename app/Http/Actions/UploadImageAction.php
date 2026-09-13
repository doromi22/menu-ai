<?php

namespace App\Http\Actions;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImageJob;
use App\Jobs\ProcessStandardImageJob;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UploadImageAction extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // 1. 로그인 필수 검증
        if (!Auth::check()) {
            return response()->json(['message' => 'ログインが必要です。'], 401);
        }

        $user = Auth::user();

        // 2. 크레딧 잔액 검증
        if ($user->credits < 1) {
            return response()->json([
                'message' => 'クレジットが不足しています。プランの購入をご検討ください。'
            ], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'prompt' => 'nullable|string|max:255',
        ]);

        // 3. 크레딧 1회 차감
        $user->decrement('credits', 1);

        // 4. 원본 이미지 저장 및 DB 기록
        $path = $request->file('image')->store('images/original', 'public');
        $prompt = $request->input('prompt', 'Japanese wooden table, soft natural sunlight');

        $image = Image::create([
            'user_id' => $user->id,
            'original_path' => $path,
            'prompt' => $prompt,
            'status' => 'pending',
        ]);

        // 5. Job 실행 (config/services.php image_processing.mode)
        if (config('services.image_processing.mode') === 'premium') {
            ProcessImageJob::dispatch($image, $prompt);
        } else {
            $templateId = config('services.image_processing.standard_templates')[$prompt] ?? null;
            ProcessStandardImageJob::dispatch($image, $templateId);
        }

        return response()->json([
            'message' => '画像のアップロードに成功しました。',
            'data' => [
                'id' => $image->id,
                'status' => $image->status,
                'original_url' => asset('storage/' . $image->original_path),
                'remaining_credits' => $user->credits,
            ]
        ], 201);
    }
}