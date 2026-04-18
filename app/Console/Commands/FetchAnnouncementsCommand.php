<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Announcements\Actions\SyncAnnouncements;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;

class FetchAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:fetch
                            {--source= : Fetch only the specified source by config key}';

    protected $description = '從各來源擷取最新公告資訊';

    public function handle(SyncAnnouncements $syncAnnouncements): int
    {
        $sources = collect(config('announcements.sources', []))
            ->map(fn (array $config, string $key): AnnouncementSourceConfigDTO => AnnouncementSourceConfigDTO::fromConfig($key, $config))
            ->filter(fn (AnnouncementSourceConfigDTO $source): bool => $source->isActive)
            ->values();

        if ($sourceKey = $this->option('source')) {
            $sources = $sources
                ->filter(fn (AnnouncementSourceConfigDTO $source): bool => $source->key === $sourceKey)
                ->values();

            if ($sources->isEmpty()) {
                $this->error('找不到指定的公告來源設定。');

                return self::FAILURE;
            }
        }

        if ($sources->isEmpty()) {
            $this->warn('找不到可用的公告來源。');

            return self::SUCCESS;
        }

        $totalNew = 0;

        foreach ($sources as $source) {
            $this->info("正在擷取：{$source->name} — {$source->category}");

            try {
                $newCount = $syncAnnouncements($source);
                $totalNew += $newCount;
                $this->info("  新增 {$newCount} 筆公告。");
            } catch (\Throwable $e) {
                $this->error("  擷取失敗：{$e->getMessage()}");
            }
        }

        $this->info("完成！共新增 {$totalNew} 筆公告。");

        return self::SUCCESS;
    }
}
