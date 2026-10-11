<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NouTools\Domains\GradeCalculator\DataTransferObjects\UpdateGradeCalculatorData;

final class GradeCalculation extends Model
{
    use HasFactory;

    protected $table = 'grade_calculations';

    protected $casts = [
        'scores' => 'json',
        'weights' => 'json',
    ];

    public function fillFromDTO(UpdateGradeCalculatorData $data): void
    {
        $this->scores = $data->scores;
        $this->weights = $data->weights;
    }

    public function studentSchedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class);
    }
}
