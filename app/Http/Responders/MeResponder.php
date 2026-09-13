<?php

namespace App\Http\Responders;

use App\Http\Responders\Payloads\UserPayload;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class MeResponder
{
    public function respond(?User $user): JsonResponse
    {
        if ($user === null) {
            return response()->json(['logged_in' => false]);
        }

        return response()->json(['logged_in' => true, 'user' => UserPayload::from($user)]);
    }
}
