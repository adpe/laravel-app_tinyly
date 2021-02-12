<?php

namespace App\Policies;

use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShortLinkPolicy
{
    use HandlesAuthorization;

    public function update(User $user, ShortLink $link)
    {
        return $user->is($link->owner);
    }
}
