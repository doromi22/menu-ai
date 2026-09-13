<?php

namespace App\Jobs;

use App\Models\Image;
use App\Services\StandardAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Standard-mode counterpart of ProcessImageJob (Premium/Modal). The HTTP
 * call and result persistence live in StandardAiService; this job only
 * owns the queue lifecycle and the credit refund.
 */
class ProcessStandardImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // StandardAiService's HTTP timeout is 60s; must stay under the queue's
    // retry_after (config/queue.php, 90s) or the job gets picked up twice.
    public int $timeout = 80;

    public function __construct(
        public Image $image,
        public ?string $templateId = null,
    ) {
    }

    public function handle(StandardAiService $service): void
    {
        $this->image->update(['status' => 'processing']);

        try {
            $originalFullPath = Storage::disk('public')->path($this->image->original_path);
            $service->process($this->image, $originalFullPath, $this->templateId);
        } catch (\Throwable $e) {
            Log::error('ProcessStandardImageJob Failed: ' . $e->getMessage());
            $this->image->update(['status' => 'failed']);
        }

        // Refund whenever no usable image came out: a transport/HTTP failure
        // above, or a pipeline REJECT / infra error that StandardAiService
        // persisted as 'failed'. REVIEW still yields an image, so no refund.
        if ($this->image->status === 'failed' && $this->image->user) {
            $this->image->user->increment('credits', 1);
        }
    }
}
