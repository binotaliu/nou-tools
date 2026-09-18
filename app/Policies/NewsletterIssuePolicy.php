<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NewsletterIssue;
use App\Models\User;

final class NewsletterIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, NewsletterIssue $newsletterIssue): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, NewsletterIssue $newsletterIssue): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, NewsletterIssue $newsletterIssue): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, NewsletterIssue $newsletterIssue): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, NewsletterIssue $newsletterIssue): bool
    {
        return $user->isAdmin();
    }
}
