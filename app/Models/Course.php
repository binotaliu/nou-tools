<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

final class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * Punctuation and whitespace that differ between the school's sources
     * for the same course title (course map, class pages, 選課注意事項 PDF),
     * e.g. `生命敘說與傳記 AI 實務` vs `生命敘說與傳記AI實務`.
     *
     * @var list<string>
     */
    public const array TITLE_PUNCTUATION = ['（', '）', '(', ')', '：', ':', '～', '~', '—', '－', '-', '–', '　', ' '];

    protected $fillable = [
        'name',
        'term',
        'is_special_program_only',
        'description_url',
        'credit_type',
        'credits',
        'department',
        'in_person_class_type',
        'media',
        'multimedia_url',
        'nature',
        'midterm_date',
        'final_date',
        'exam_time_start',
        'exam_time_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'midterm_date' => 'date',
            'final_date' => 'date',
            'is_special_program_only' => 'bool',
        ];
    }

    /**
     * Courses 一般生 can pick, i.e. not offered only inside a 專班.
     *
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('is_special_program_only', false);
    }

    /**
     * Courses whose name equals the given title once {@see self::TITLE_PUNCTUATION}
     * is stripped from both, so importers reading different sources find
     * the same row instead of creating a near-duplicate.
     *
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    public function scopeWhereNameMatches(Builder $query, string $name): Builder
    {
        $strippedColumn = $query->getModel()->qualifyColumn('name');

        foreach (self::TITLE_PUNCTUATION as $_) {
            $strippedColumn = "REPLACE({$strippedColumn}, ?, '')";
        }

        return $query->whereRaw(
            "{$strippedColumn} = ?",
            [...self::TITLE_PUNCTUATION, Str::withoutCourseTitlePunctuation($name)],
        );
    }

    /**
     * @return HasMany<CourseClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class);
    }

    public function textbook(): HasOne
    {
        return $this->hasOne(Textbook::class);
    }

    /**
     * @return HasMany<PreviousExam, $this>
     */
    public function previousExams(): HasMany
    {
        return $this->hasMany(PreviousExam::class, 'course_name', 'name');
    }
}
