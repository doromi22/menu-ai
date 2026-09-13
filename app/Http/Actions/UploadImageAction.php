<?php

namespace App\Http\Actions;

use App\Domain\Image\InsufficientCreditsException;
use App\Domain\Image\UploadImage;
use App\Http\Responders\UploadImageResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UploadImageAction
{
    public function __construct(
        private UploadImage $uploadImage,
        private UploadImageResponder $responder,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return $this->responder->unauthenticated();
        }

        // Cheap balance check before validation so a user with no credits
        // gets "insufficient credits" rather than a validation error; the
        // domain still re-checks atomically when actually spending.
        if ($user->credits < 1) {
            return $this->responder->insufficientCredits();
        }

        $input = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'prompt' => 'nullable|string|max:255',
        ]);

        try {
            $image = $this->uploadImage->handle($user, $request->file('image'), $input['prompt'] ?? null);
        } catch (InsufficientCreditsException) {
            return $this->responder->insufficientCredits();
        }

        return $this->responder->uploaded($image, $user);
    }
}
