<?php

declare(strict_types=1);

namespace App\Enums;

enum WikiSourceDocumentStatus: string
{
    /** Queued, not picked up yet. */
    case Pending = 'pending';

    case Fetching = 'fetching';

    /** Sections are stored and the editor can read them. */
    case Ready = 'ready';

    /** The last fetch failed; `failure_reason` says why. Earlier sections are kept. */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '等待擷取',
            self::Fetching => '擷取中',
            self::Ready => '可使用',
            self::Failed => '擷取失敗',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Fetching => 'info',
            self::Ready => 'success',
            self::Failed => 'danger',
        };
    }
}
