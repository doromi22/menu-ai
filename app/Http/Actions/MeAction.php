<?php

namespace App\Http\Actions;

use App\Http\Responders\MeResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MeAction
{
    public function __construct(private MeResponder $responder) {}

    public function __invoke(): JsonResponse
    {
        return $this->responder->respond(Auth::user());
    }
}
