<?php

namespace App\Jobs;

use App\Models\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180; // 클라우드 GPU 부팅 대기를 위해 타임아웃 180초 설정

    protected Image $image;
    protected string $prompt;

    public function __construct(Image $image, string $prompt = 'izakaya')
    {
        $this->image = $image;
        $this->prompt = $prompt;
    }

    public function handle(): void
    {
        $this->image->update(['status' => 'processing']);

        try {
            $originalFullPath = Storage::disk('public')->path($this->image->original_path);
            
            // Modal 배포 URL (.env의 AI_SERVICE_URL)
            $aiUrl = env('AI_SERVICE_URL');
            if (!$aiUrl) {
                throw new \RuntimeException('AI_SERVICE_URL is not configured');
            }

            $response = Http::timeout(180)
                ->attach('image', file_get_contents($originalFullPath), basename($originalFullPath))
                ->post($aiUrl, [
                    'prompt' => $this->prompt,
                ]);

            if (!$response->successful()) {
                throw new \Exception('AI Engine API Error: ' . $response->body());
            }

            // 결과 이미지 저장
            $processedFilename = 'images/processed/' . Str::random(40) . '.jpg';
            Storage::disk('public')->put($processedFilename, $response->body());

            // 완료 상태 업데이트
            $this->image->update([
                'processed_path' => $processedFilename,
                'status' => 'completed',
            ]);

        } catch (\Throwable $e) {
            \Log::error('ProcessImageJob Failed: ' . $e->getMessage());
            
            // 실패 상태 업데이트
            $this->image->update(['status' => 'failed']);
            
            // 실패 시 사용자 크레딧 1회 환불
            if ($this->image->user) {
                $this->image->user->increment('credits', 1);
            }
        }
    }
}