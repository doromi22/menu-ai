<?php

namespace App\Domain\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Sign-up rules: disposable addresses are refused, and free credits are
 * only granted when neither the IP nor the device fingerprint signed up
 * within the last 30 days (one free trial per person, not per address).
 */
class RegisterUser
{
    public const FREE_CREDITS = 3;

    private const ABUSE_WINDOW_DAYS = 30;

    private const DISPOSABLE_DOMAINS = [
        'mailinator.com', 'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'sharklasers.com',
    ];

    /**
     * @throws DisposableEmailException
     */
    public function handle(string $name, string $email, string $password, ?string $ip, ?string $deviceFingerprint): User
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            throw new DisposableEmailException($domain);
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'credits' => $this->isRepeatSignup($ip, $deviceFingerprint) ? 0 : self::FREE_CREDITS,
            'signup_ip' => $ip,
            'device_fingerprint' => $deviceFingerprint,
        ]);
    }

    private function isRepeatSignup(?string $ip, ?string $deviceFingerprint): bool
    {
        return User::query()
            ->where(function ($query) use ($ip, $deviceFingerprint) {
                $query->where('signup_ip', $ip);
                if ($deviceFingerprint) {
                    $query->orWhere('device_fingerprint', $deviceFingerprint);
                }
            })
            ->where('created_at', '>=', now()->subDays(self::ABUSE_WINDOW_DAYS))
            ->exists();
    }
}
