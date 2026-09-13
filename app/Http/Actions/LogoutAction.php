<?php

namespace App\Http\Actions;

use App\Http\Responders\LogoutResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutAction
{
    public function __construct(private LogoutResponder $responder) {}

    public function __invoke(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->responder->loggedOut();
    }
}
