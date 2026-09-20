<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MusicPlaylist;
use App\Models\User;

final class MusicPlaylistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, MusicPlaylist $musicPlaylist): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MusicPlaylist $musicPlaylist): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MusicPlaylist $musicPlaylist): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, MusicPlaylist $musicPlaylist): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, MusicPlaylist $musicPlaylist): bool
    {
        return $user->isAdmin();
    }
}
