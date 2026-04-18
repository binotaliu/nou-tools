<?php

namespace NouTools\Domains\Announcements\Contracts;

use Illuminate\Support\Collection;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\DataTransferObjects\FetchedAnnouncementDTO;

interface AnnouncementFetcher
{
    /**
     * @return Collection<int, FetchedAnnouncementDTO>
     */
    public function fetch(AnnouncementSourceConfigDTO $source): Collection;
}
