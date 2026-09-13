<?php

namespace App\Http\Actions;

use App\Domain\Image\ListUserImages;
use App\Http\Responders\UserImagesResponder;
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

        return $this->responder->respond($this->listUserImages->handle($user));
    }
}
