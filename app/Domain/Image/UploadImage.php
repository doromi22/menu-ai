<?php

namespace App\Domain\Image;

use App\Jobs\ProcessImageJob;
use App\Jobs\ProcessStandardImageJob;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Spends one credit, stores the original and queues processing on the
 * configured engine (config/services.php `image_processing`). Failed
 * processing is refunded later by the job itself.
 */
class UploadImage
{
    private const DEFAULT_PRESET = 'Japanese wooden table, soft natural sunlight';

    /**
     * @throws InsufficientCreditsException
     */
    public function handle(User $user, UploadedFile $file, ?string $preset): Image
    {
        // Atomic check-and-spend: two concurrent uploads can't both pass a
        // separate "credits >= 1" read and drive the balance negative.
        $spent = User::whereKey($user->getKey())->where('credits', '>=', 1)->decrement('credits');
        if ($spent === 0) {
            throw new InsufficientCreditsException();
        }
        $user->refresh();

        $preset ??= self::DEFAULT_PRESET;

        try {
            $image = Image::create([
                'user_id' => $user->id,
                'original_path' => $file->store('images/original', 'public'),
                'prompt' => $preset,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            $user->increment('credits');
            throw $e;
        }

        $this->dispatch($image, $preset);

        return $image;
    }

    private function dispatch(Image $image, string $preset): void
    {
        if (config('services.image_processing.mode') === 'premium') {
            ProcessImageJob::dispatch($image, $preset);

            return;
        }

        $templateId = config('services.image_processing.standard_templates')[$preset] ?? null;
        ProcessStandardImageJob::dispatch($image, $templateId);
    }
}
