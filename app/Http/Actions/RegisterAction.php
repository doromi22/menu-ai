<?php

namespace App\Http\Actions;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterAction extends Controller
{
    // 차단할 대표적인 1회용 이메일 도메인 목록
    private array $disposableDomains = [
        'mailinator.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'sharklasers.com'
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'device_fingerprint' => 'nullable|string',
        ]);

        $emailDomain = substr(strrchr($request->email, "@"), 1);
        if (in_array(strtolower($emailDomain), $this->disposableDomains)) {
            return response()->json(['message' => '使い捨てメールアドレスは登録できません。'], 422);
        }

        $ip = $request->ip();
        $fingerprint = $request->input('device_fingerprint');

        // 어뷰징 검사: 동일 IP 또는 동일 기기 지문으로 최근 30일 내 가입한 이력이 있는지 확인
        $isAbuseSuspect = User::where(function ($query) use ($ip, $fingerprint) {
            $query->where('signup_ip', $ip);
            if ($fingerprint) {
                $query->orWhere('device_fingerprint', $fingerprint);
            }
        })
        ->where('created_at', '>=', now()->subDays(30))
        ->exists();

        // 어뷰징 의심 시 크레딧 0개, 정상 가입 시 무료 크레딧 3개 지급
        $initialCredits = $isAbuseSuspect ? 0 : 3;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'credits' => $initialCredits,
            'signup_ip' => $ip,
            'device_fingerprint' => $fingerprint,
        ]);

        Auth::login($user);

        return response()->json([
            'message' => '登録が完了しました。',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'credits' => $user->credits,
            ]
        ], 201);
    }
}