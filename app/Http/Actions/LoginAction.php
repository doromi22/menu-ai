<?php

namespace App\Http\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginAction extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            $user = Auth::user();
            return response()->json([
                'message' => 'ログインしました。',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'credits' => $user->credits,
                ]
            ]);
        }

        return response()->json(['message' => 'メールアドレスまたはパスワードが正しくありません。'], 401);
    }
}