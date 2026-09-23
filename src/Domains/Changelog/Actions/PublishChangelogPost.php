<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\Actions;

use App\Enums\ChangelogPostStatus;
use App\Models\ChangelogPost;
use DomainException;
use Illuminate\Support\Facades\Date;

final readonly class PublishChangelogPost
{
    /**
     * @throws DomainException when the post has nothing to publish
     */
    public function __invoke(ChangelogPost $post): ChangelogPost
    {
        if ($post->isPublished()) {
            return $post;
        }

        if (blank($post->body)) {
            throw new DomainException("更新日誌「{$post->title}」沒有內容，無法發布。");
        }

        $post->status = ChangelogPostStatus::Published;
        $post->published_at = Date::now();
        $post->saveOrFail();

        return $post;
    }
}
