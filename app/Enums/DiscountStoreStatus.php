<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountStoreStatus: string
{
    case Pending = 'pending';
    case Online = 'online';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '待審核',
            self::Online => '上架中',
            self::Expired => '已失效',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Online => 'success',
            self::Expired => 'danger',
        };
    }
}
