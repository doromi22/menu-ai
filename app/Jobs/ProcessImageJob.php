<?php

namespace App\Jobs;

use App\Models\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Premium engine (opt-in, IMAGE_PROCESSING_MODE=premium): the earlier
 * generative pipeline hosted on Modal. It returns raw JPEG bytes and no
 * integrity metadata, unlike ProcessStandardImageJob.
 */
class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Allows for a cold GPU container on Modal. Must stay below the queue's
    // retry_after (config/queue.php) or the job would be picked up twice.
    public int $timeout = 180;

    public function __construct(
        protected Image $image,
        protected string $prompt = 'izakaya',
    ) {}

    public function handle(): void
    {
        $this->image->update(['status' => 'processing']);

        try {
            $aiUrl = config('services.premium_ai.url');
            if (! $aiUrl) {
                throw new \RuntimeException('AI_SERVICE_URL is not configured');
            }

            $originalFullPath = Storage::disk('public')->path($this->image->original_path);
            $response = Http::timeout(180)
                ->attach('image', file_get_contents($originalFullPath), basename($originalFullPath))
                ->post($aiUrl, ['prompt' => $this->prompt]);

            if (! $response->successful()) {
                throw new \RuntimeException('AI Engine API Error: '.$response->body());
            }

            $processedFilename = 'images/processed/'.Str::random(40).'.jpg';
            Storage::disk('public')->put($processedFilename, $response->body());

            $this->image->update([
                'processed_path' => $processedFilename,
                'status' => 'completed',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessImageJob Failed: '.$e->getMessage());

            $this->image->update(['status' => 'failed']);
            $this->image->user?->increment('credits', 1);
        }
    }
}
