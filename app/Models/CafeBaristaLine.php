<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BaristaTrigger;
use Database\Factories\CafeBaristaLineFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CafeBaristaLine extends Model
{
    /** @use HasFactory<CafeBaristaLineFactory> */
    use HasFactory;

    // Filament fills these from the admin form; nothing public writes them.
    protected $fillable = ['trigger', 'conditions', 'text', 'mood', 'replies', 'priority', 'weight', 'is_active'];

    protected $casts = [
        'trigger' => BaristaTrigger::class,
        'conditions' => 'array',
        'replies' => 'array',
        'priority' => 'integer',
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @param  Builder<CafeBaristaLine>  $query
     * @return Builder<CafeBaristaLine>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
