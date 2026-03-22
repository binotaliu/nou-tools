<?php

namespace NouTools\Domains\Courses\ViewModels\Api;

use App\Models\PreviousExam;
use Spatie\LaravelData\Data;

/**
 * Past exam reference links for a course (考古題).
 */
final class PreviousExamViewModel extends Data
{
    public function __construct(
        public string $term,
        public ?string $midtermReferencePrimary,
        public ?string $midtermReferenceSecondary,
        public ?string $finalReferencePrimary,
        public ?string $finalReferenceSecondary,
    ) {}

    public static function fromModel(PreviousExam $exam): self
    {
        return new self(
            term: $exam->term,
            midtermReferencePrimary: $exam->midterm_reference_primary,
            midtermReferenceSecondary: $exam->midterm_reference_secondary,
            finalReferencePrimary: $exam->final_reference_primary,
            finalReferenceSecondary: $exam->final_reference_secondary,
        );
    }
}
