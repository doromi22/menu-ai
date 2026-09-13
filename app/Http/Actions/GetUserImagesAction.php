<?php

namespace App\Http\Actions;

use App\Domain\Image\ListUserImages;
use App\Domain\Image\ProcessingOutcome;
use App\Http\Responders\UserImagesResponder;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GetUserImagesAction
{
    public function __construct(
        private ListUserImages $listUserImages,
        private UserImagesResponder $responder,
    ) {}

    public function __invoke(): JsonResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return $this->responder->unauthenticated();
        }

        $images = $this->listUserImages->handle($user);
        $reviewRequired = $images
            ->mapWithKeys(fn (Image $image) => [$image->id => ProcessingOutcome::imageRequiresReview($image)])
            ->all();

        return $this->responder->respond($images, $reviewRequired);
    }
}
