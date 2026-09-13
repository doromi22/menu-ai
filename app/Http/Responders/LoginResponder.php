<?php

namespace App\Http\Responders;

use App\Http\Responders\Payloads\UserPayload;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LoginResponder
{
    public function loggedIn(User $user): JsonResponse
    {
        return response()->json(['message' => 'ログインしました。', 'user' => UserPayload::from($user)]);
    }

    public function invalidCredentials(): JsonResponse
    {
        return response()->json(['message' => 'メールアドレスまたはパスワードが正しくありません。'], 401);
    }
}
