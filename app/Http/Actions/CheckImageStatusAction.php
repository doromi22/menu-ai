<?php

namespace App\Http\Actions;

use App\Domain\Image\ProcessingOutcome;
use App\Http\Responders\ImageStatusResponder;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CheckImageStatusAction
{
    public function __construct(private ImageStatusResponder $responder) {}

    public function __invoke(Image $image): JsonResponse
    {
        if ($image->user_id !== Auth::id()) {
            return $this->responder->forbidden();
        }

        return $this->responder->respond(
            $image,
            ProcessingOutcome::imageRequiresReview($image),
            ProcessingOutcome::messageForImage($image),
        );
    }
}
