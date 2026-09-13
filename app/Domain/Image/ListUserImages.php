<?php

namespace App\Domain\Image;

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListUserImages
{
    /**
     * @return Collection<int, Image>
     */
    public function handle(User $user, int $limit = 20): Collection
    {
        return Image::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
