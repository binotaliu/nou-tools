<?php

namespace NouTools\Domains\Announcements\Actions;

use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\DataTransferObjects\FetchedAnnouncementDTO;
use NouTools\Domains\Announcements\Fetchers\AnnouncementFetcherFactory;

final readonly class SyncAnnouncements
{
    public function __construct(
        private AnnouncementFetcherFactory $fetcherFactory,
    ) {}

    public function __invoke(AnnouncementSourceConfigDTO $source): int
    {
        $fetcher = $this->fetcherFactory->make($source->fetcherType);
        $fetched = $fetcher->fetch($source);

        $now = now();
        $newCount = 0;

        DB::transaction(function () use ($source, $fetched, $now, &$newCount) {
            $fetchedSourceIds = [];

            /** @var FetchedAnnouncementDTO $dto */
            foreach ($fetched as $dto) {
                $fetchedSourceIds[] = $dto->sourceId;

                $existing = Announcement::query()
                    ->where('source_key', $source->key)
                    ->where('source_id', $dto->sourceId)
                    ->first();

                if ($existing !== null) {
                    $existing->source_name = $source->name;
                    $existing->category = $source->category;
                    $existing->title = $dto->title;
                    $existing->url = $dto->url;
                    $existing->tags = $dto->tags;
                    $existing->published_at = $dto->publishedAt;
                    $existing->fetched_at = $now;
                    $existing->expired_at = null;
                    $existing->saveOrFail();

                    continue;
                }

                $announcement = new Announcement;
                $announcement->source_key = $source->key;
                $announcement->source_name = $source->name;
                $announcement->category = $source->category;
                $announcement->source_id = $dto->sourceId;
                $announcement->title = $dto->title;
                $announcement->url = $dto->url;
                $announcement->tags = $dto->tags;
                $announcement->published_at = $dto->publishedAt;
                $announcement->fetched_at = $now;
                $announcement->saveOrFail();

                $newCount++;
            }

            if ($source->tracksExpiry && $fetchedSourceIds !== []) {
                Announcement::query()
                    ->where('source_key', $source->key)
                    ->whereNull('expired_at')
                    ->whereNotIn('source_id', $fetchedSourceIds)
                    ->update(['expired_at' => $now]);
            }
        });

        return $newCount;
    }
}
