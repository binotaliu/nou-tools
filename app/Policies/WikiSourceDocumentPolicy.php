<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WikiSourceDocument;

final class WikiSourceDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, WikiSourceDocument $wikiSourceDocument): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WikiSourceDocument $wikiSourceDocument): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, WikiSourceDocument $wikiSourceDocument): bool
    {
        return $user->isAdmin();
    }
}
