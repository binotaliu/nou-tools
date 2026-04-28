<?php

namespace App\Models;

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use Database\Factories\DiscountStoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiscountStore extends Model
{
    /** @use HasFactory<DiscountStoreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'type',
        'category_id',
        'city',
        'district',
        'address',
        'verification_method',
        'discount_details',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DiscountStoreStatus::class,
            'type' => DiscountStoreType::class,
        ];
    }

    /**
     * @return BelongsTo<DiscountStoreCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DiscountStoreCategory::class, 'category_id');
    }

    /**
     * @return HasMany<DiscountStoreReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(DiscountStoreReport::class, 'store_id');
    }

    /**
     * @return HasMany<DiscountStoreComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(DiscountStoreComment::class, 'store_id');
    }

    public function latestReport(): ?HasOne
    {
        return $this->hasOne(DiscountStoreReport::class, 'store_id')
            ->latestOfMany();
    }
}
