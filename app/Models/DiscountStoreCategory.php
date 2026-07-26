<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DiscountStoreCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'sort_order',
    ];

    /**
     * @return HasMany<DiscountStore, $this>
     */
    public function stores(): HasMany
    {
        return $this->hasMany(DiscountStore::class, 'category_id');
    }
}
