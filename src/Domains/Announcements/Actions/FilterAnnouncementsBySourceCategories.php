<?php

namespace NouTools\Domains\Announcements\Actions;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;

final readonly class FilterAnnouncementsBySourceCategories
{
    /**
     * @param  Builder<Announcement>  $query
     * @param  array<string, array<int, string>>  $selectedSourceCategories
     * @return Builder<Announcement>
     */
    public function __invoke(Builder $query, array $selectedSourceCategories): Builder
    {
        if ($selectedSourceCategories === []) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($selectedSourceCategories) {
            foreach ($selectedSourceCategories as $source => $categories) {
                $subQuery->orWhere(function (Builder $sourceCategoryQuery) use ($source, $categories) {
                    $sourceCategoryQuery
                        ->where('source_name', $source)
                        ->whereIn('category', $categories);
                });
            }
        });
    }
}
