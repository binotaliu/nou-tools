<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CafeMenuItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CafeMenuItem extends Model
{
    /** @use HasFactory<CafeMenuItemFactory> */
    use HasFactory;

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * @param  Builder<CafeMenuItem>  $query
     * @return Builder<CafeMenuItem>
     */
    public function scopeOnMenu(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }
}
