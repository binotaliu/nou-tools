<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\NewsletterReactionType;
use App\Models\NewsletterIssue;
use App\Models\NewsletterReaction;
use Illuminate\Contracts\Session\Session;
use NouTools\Domains\Newsletter\ViewModels\NewsletterReactionOptionViewModel;
use NouTools\Domains\Newsletter\ViewModels\NewsletterReactionsViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class SummarizeNewsletterReactions
{
    public function __invoke(NewsletterIssue $issue, Session $session): NewsletterReactionsViewModel
    {
        $counts = NewsletterReaction::query()
            ->where('newsletter_issue_id', $issue->id)
            ->selectRaw('reaction, count(*) as total')
            ->groupBy('reaction')
            ->pluck('total', 'reaction');

        $mine = NewsletterReaction::query()
            ->where('newsletter_issue_id', $issue->id)
            ->where('session_hash', NewsletterReaction::sessionHash($session))
            ->value('reaction');

        return new NewsletterReactionsViewModel(
            options: NewsletterReactionOptionViewModel::collect(
                array_map(
                    fn (NewsletterReactionType $type): NewsletterReactionOptionViewModel => new NewsletterReactionOptionViewModel(
                        key: $type->value,
                        emoji: $type->emoji(),
                        label: $type->label(),
                        count: (int) ($counts[$type->value] ?? 0),
                    ),
                    NewsletterReactionType::cases(),
                ),
                DataCollection::class,
            ),
            mine: $mine instanceof NewsletterReactionType ? $mine->value : $mine,
        );
    }
}
