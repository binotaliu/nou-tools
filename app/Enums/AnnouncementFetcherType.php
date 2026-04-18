<?php

namespace App\Enums;

enum AnnouncementFetcherType: string
{
    case JSON_API = 'json_api';
    case HTML_SCRAPE = 'html_scrape';
    case HTML_NEWS_BOX = 'html_news_box';

    public function label(): string
    {
        return match ($this) {
            self::JSON_API => 'JSON API',
            self::HTML_SCRAPE => 'HTML 擷取',
            self::HTML_NEWS_BOX => 'HTML 公告列表',
        };
    }
}
