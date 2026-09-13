<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiGenerationService
{
    protected string $segmentationApiUrl = 'http://127.0.0.1:8001/segment';

    public function process(Image $image, string $prompt): void
    {
        $originalFullPath = Storage::disk('public')->path($image->original_path);

        if (!file_exists($originalFullPath)) {
            Log::error("Original file not found: {$originalFullPath}");
            $image->update(['status' => 'failed']);
            return;
        }

        try {
            // Python FastAPI로 원본 이미지 + 선택된 프리셋 프롬프트 전송
            $response = Http::timeout(30)->attach(
                'image',
                file_get_contents($originalFullPath),
                basename($originalFullPath)
            )->asMultipart()->post($this->segmentationApiUrl, [
                'prompt' => $prompt ?: 'Japanese dark wooden table, warm ambient lighting, izakaya style'
            ]);

            if (!$response->successful()) {
                Log::error("AI Service Error: " . $response->body());
                $image->update(['status' => 'failed']);
                return;
            }

            // 고화질 합성 결과물 저장
            $processedRelativePath = 'images/processed/' . uniqid('composed_') . '.jpg';
            Storage::disk('public')->put($processedRelativePath, $response->body());

            $image->update([
                'processed_path' => $processedRelativePath,
                'status' => 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::error("ProcessImageJob Exception: " . $e->getMessage());
            $image->update(['status' => 'failed']);
        }
    }
}