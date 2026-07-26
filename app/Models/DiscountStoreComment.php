<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DiscountStoreCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DiscountStoreComment extends Model
{
    /** @use HasFactory<DiscountStoreCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'nickname',
        'content',
        'is_approved',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DiscountStore, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(DiscountStore::class, 'store_id');
    }
}
