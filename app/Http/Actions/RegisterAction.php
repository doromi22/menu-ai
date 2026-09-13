<?php

namespace App\Http\Actions;

use App\Domain\Auth\DisposableEmailException;
use App\Domain\Auth\RegisterUser;
use App\Http\Responders\RegisterResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterAction
{
    public function __construct(
        private RegisterUser $registerUser,
        private RegisterResponder $responder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'device_fingerprint' => 'nullable|string',
        ]);

        try {
            $user = $this->registerUser->handle(
                $input['name'],
                $input['email'],
                $input['password'],
                $request->ip(),
                $input['device_fingerprint'] ?? null,
            );
        } catch (DisposableEmailException) {
            return $this->responder->disposableEmail();
        }

        Auth::login($user);

        return $this->responder->registered($user);
    }
}
