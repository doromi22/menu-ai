<?php

namespace App\Http\Responders;

use App\Http\Responders\Payloads\UserPayload;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterResponder
{
    public function registered(User $user): JsonResponse
    {
        return response()->json(['message' => '登録が完了しました。', 'user' => UserPayload::from($user)], 201);
    }

    public function disposableEmail(): JsonResponse
    {
        return response()->json(['message' => '使い捨てメールアドレスは登録できません。'], 422);
    }
}
