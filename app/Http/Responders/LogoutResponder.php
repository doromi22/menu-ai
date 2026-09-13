<?php

namespace App\Http\Responders;

use Illuminate\Http\JsonResponse;

class LogoutResponder
{
    public function loggedOut(): JsonResponse
    {
        return response()->json(['message' => 'ログアウトしました。']);
    }
}
