<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case DiscountStore = 'discount_store';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::DiscountStore => 'DiscountStore',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => '所有權限',
            self::DiscountStore => 'DiscountStores / DiscountStoreCategories 管理',
        };
    }
}
