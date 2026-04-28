<?php

namespace App\Models;

use Database\Factories\DiscountStoreReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountStoreReport extends Model
{
    /** @use HasFactory<DiscountStoreReportFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'is_valid',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
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
