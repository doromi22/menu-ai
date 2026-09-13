<?php

namespace App\Http\Responders\Payloads;

use App\Models\User;

final class UserPayload
{
    /**
     * @return array{id: int, name: string, credits: int}
     */
    public static function from(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'credits' => $user->credits,
        ];
    }
}
