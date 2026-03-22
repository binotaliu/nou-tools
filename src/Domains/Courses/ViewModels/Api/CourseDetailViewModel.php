<?php

namespace NouTools\Domains\Courses\ViewModels\Api;

use App\Models\Course;
use App\Models\PreviousExam;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Full course detail including exam dates, textbook, previous exams, and class sessions.
 */
final class CourseDetailViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $term,
        public ?string $descriptionUrl,
        public ?string $creditType,
        public ?int $credits,
        public ?string $department,
        public ?string $nature,

        // Exam dates
        public ?string $midtermDate,
        public ?string $finalDate,
        public ?string $examTimeStart,
        public ?string $examTimeEnd,

        // Textbook
        public ?TextbookViewModel $textbook,

        // Previous exams
        #[DataCollectionOf(PreviousExamViewModel::class)]
        public DataCollection $previousExams,

        // Classes with in-person sessions
        #[DataCollectionOf(ClassWithSessionsViewModel::class)]
        public DataCollection $classes,
    ) {}

    /**
     * @param  Collection<int, PreviousExam>  $previousExams
     */
    public static function fromModel(Course $course, Collection $previousExams): self
    {
        return new self(
            id: $course->id,
            name: $course->name,
            term: $course->term,
            descriptionUrl: $course->description_url,
            creditType: $course->credit_type,
            credits: $course->credits,
            department: $course->department,
            nature: $course->nature,
            midtermDate: $course->midterm_date ? (string) $course->midterm_date : null,
            finalDate: $course->final_date ? (string) $course->final_date : null,
            examTimeStart: $course->exam_time_start,
            examTimeEnd: $course->exam_time_end,
            textbook: $course->textbook ? TextbookViewModel::fromModel($course->textbook) : null,
            previousExams: PreviousExamViewModel::collect(
                $previousExams->map(fn ($e) => PreviousExamViewModel::fromModel($e)),
                DataCollection::class,
            ),
            classes: ClassWithSessionsViewModel::collect(
                $course->classes->map(fn ($c) => ClassWithSessionsViewModel::fromModel($c)),
                DataCollection::class,
            ),
        );
    }
}
