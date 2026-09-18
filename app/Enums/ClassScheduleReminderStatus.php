<?php

declare(strict_types=1);

namespace App\Enums;

enum ClassScheduleReminderStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
}
