<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use Illuminate\Support\Collection;
use NouTools\Domains\Directory\Actions\ListCentersInDirectoryOrder;
use NouTools\Domains\Newsletter\ViewModels\NewsletterIssueViewModel;

final readonly class ListNewsletterFeedIssues
{
    public function __construct(
        private RenderNewsletterMarkdown $renderNewsletterMarkdown,
        private ListCentersInDirectoryOrder $listCentersInDirectoryOrder,
    ) {}

    /**
     * The latest published issues, newest first, for the Atom feed.
     *
     * @return Collection<int, NewsletterIssueViewModel>
     */
    public function __invoke(int $limit = 20): Collection
    {
        $centerOrder = ($this->listCentersInDirectoryOrder)()->pluck('name')->all();

        return NewsletterIssue::query()
            ->published()
            ->with(['items', 'columns'])
            ->orderByDesc('publishes_on')
            ->limit($limit)
            ->get()
            ->map(fn (NewsletterIssue $issue): NewsletterIssueViewModel => NewsletterIssueViewModel::fromModel($issue, $this->renderNewsletterMarkdown, $centerOrder));
    }
}
