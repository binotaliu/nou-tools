<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementFetcherType: string
{
    case JSON_API = 'json_api';
    case HTML_SCRAPE = 'html_scrape';
    case HTML_NEWS_BOX = 'html_news_box';
    case SCHOOL_HP = 'school_hp';

    public function label(): string
    {
        return match ($this) {
            self::JSON_API => 'JSON API',
            self::HTML_SCRAPE => 'HTML 擷取',
            self::HTML_NEWS_BOX => 'HTML 公告列表',
            self::SCHOOL_HP => 'School HP',
        };
    }
}
