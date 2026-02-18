<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentSchedule extends Model
{
    protected $fillable = [
        'uuid',
        'session_token',
        'name',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return HasMany<StudentScheduleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StudentScheduleItem::class);
    }
}
