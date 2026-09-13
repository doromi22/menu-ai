<?php

namespace App\Http\Actions;

use App\Http\Responders\LoginResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
    public function __construct(private LoginResponder $responder) {}

    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            return $this->responder->invalidCredentials();
        }

        $request->session()->regenerate();

        return $this->responder->loggedIn(Auth::user());
    }
}
