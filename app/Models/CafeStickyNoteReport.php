<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CafeStickyNoteReport extends Model
{
    /**
     * @return BelongsTo<CafeStickyNote, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(CafeStickyNote::class, 'cafe_sticky_note_id');
    }
}
