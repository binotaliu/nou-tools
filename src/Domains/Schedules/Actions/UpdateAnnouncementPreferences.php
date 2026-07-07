<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Announcements\Actions\ListAnnouncementSourceCategories;
use NouTools\Domains\Schedules\DataTransferObjects\AnnouncementPreferencesUpsertData;

final readonly class UpdateAnnouncementPreferences
{
    public function __construct(
        private ListAnnouncementSourceCategories $listAnnouncementSourceCategories,
    ) {}

    public function __invoke(StudentSchedule $schedule, AnnouncementPreferencesUpsertData $input): StudentSchedule
    {
        $catalog = ($this->listAnnouncementSourceCategories)();

        return DB::transaction(function () use ($schedule, $input, $catalog) {
            $schedule->announcement_categories = $this->sanitize($input->announcementCategories, $catalog);
            $schedule->saveOrFail();

            return $schedule;
        });
    }

    /**
     * @param  array<string, array<int, string>>  $selectedSourceCategories
     * @param  Collection<string, Collection<int, string>>  $catalog
     * @return array<string, array<int, string>>
     */
    private function sanitize(array $selectedSourceCategories, Collection $catalog): array
    {
        return collect($selectedSourceCategories)
            ->filter(fn ($categories, $source): bool => is_string($source) && $catalog->has($source))
            ->map(function ($categories, string $source) use ($catalog): array {
                return collect(is_array($categories) ? $categories : [])
                    ->intersect($catalog->get($source, collect()))
                    ->values()
                    ->all();
            })
            ->filter(fn (array $categories): bool => $categories !== [])
            ->all();
    }
}
