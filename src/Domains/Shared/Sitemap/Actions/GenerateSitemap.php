<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Sitemap\Actions;

use App\Enums\ArticleType;
use App\Enums\DiscountStoreStatus;
use App\Models\Course;
use App\Models\DiscountStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use NouTools\Domains\Articles\Actions\ShowArticlePage;
use NouTools\Domains\Shared\Sitemap\ViewModels\SitemapUrlViewModel;

final readonly class GenerateSitemap
{
    public function __construct(
        private ShowArticlePage $showArticlePage,
    ) {}

    /**
     * @return Collection<int, SitemapUrlViewModel>
     */
    public function __invoke(): Collection
    {
        return Collection::make([
            new SitemapUrlViewModel(url: route('home'), changeFrequency: 'daily', priority: 1.0),
            new SitemapUrlViewModel(url: route('alt-uu'), changeFrequency: 'monthly', priority: 0.5),
            new SitemapUrlViewModel(url: route('announcements.index'), changeFrequency: 'hourly', priority: 0.8),
            new SitemapUrlViewModel(url: route('directory.index'), changeFrequency: 'monthly', priority: 0.6),
            new SitemapUrlViewModel(url: route('course.schedule'), changeFrequency: 'weekly', priority: 0.7),
            new SitemapUrlViewModel(url: route('discount-stores.index'), changeFrequency: 'daily', priority: 0.7),
            new SitemapUrlViewModel(url: route('discount-stores.create'), changeFrequency: 'yearly', priority: 0.3),
        ])
            ->merge($this->courseUrls())
            ->merge($this->discountStoreUrls())
            ->merge($this->articleUrls());
    }

    /**
     * @return Collection<int, SitemapUrlViewModel>
     */
    private function courseUrls(): Collection
    {
        return Course::query()->get(['id', 'updated_at'])
            ->map(fn (Course $course): SitemapUrlViewModel => new SitemapUrlViewModel(
                url: route('course.show', $course),
                lastModified: $course->updated_at,
                changeFrequency: 'weekly',
                priority: 0.6,
            ));
    }

    /**
     * @return Collection<int, SitemapUrlViewModel>
     */
    private function discountStoreUrls(): Collection
    {
        return DiscountStore::query()
            ->where('status', DiscountStoreStatus::Online)
            ->get(['id', 'updated_at'])
            ->map(fn (DiscountStore $store): SitemapUrlViewModel => new SitemapUrlViewModel(
                url: route('discount-stores.show', $store),
                lastModified: $store->updated_at,
                changeFrequency: 'weekly',
                priority: 0.5,
            ));
    }

    /**
     * @return Collection<int, SitemapUrlViewModel>
     */
    private function articleUrls(): Collection
    {
        return Collection::make(ArticleType::cases())
            ->flatMap(function (ArticleType $type): Collection {
                $urls = Collection::make([
                    new SitemapUrlViewModel(url: route('articles.index', $type), changeFrequency: 'weekly', priority: 0.6),
                ]);

                return $urls->merge($this->articleSlugUrls($type));
            });
    }

    /**
     * @return Collection<int, SitemapUrlViewModel>
     */
    private function articleSlugUrls(ArticleType $type): Collection
    {
        $directory = resource_path("articles/{$type->directory()}");

        if (! File::isDirectory($directory)) {
            return Collection::make();
        }

        return Collection::make(File::files($directory))
            ->filter(fn ($file): bool => $file->getExtension() === 'md'
                && ! in_array($file->getFilenameWithoutExtension(), ['_index', '_sidebar'], true))
            ->map(function ($file) use ($type): ?SitemapUrlViewModel {
                $slug = $file->getFilenameWithoutExtension();
                $page = ($this->showArticlePage)($type, $slug);

                if (! $page) {
                    return null;
                }

                return new SitemapUrlViewModel(
                    url: route('articles.show', [$type, $slug]),
                    lastModified: $page->article->updatedAt ?? $page->article->publishedAt,
                    changeFrequency: 'monthly',
                    priority: 0.5,
                );
            })
            ->filter()
            ->values();
    }
}
