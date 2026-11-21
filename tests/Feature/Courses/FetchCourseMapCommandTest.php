<?php

use App\Models\Course;
use Illuminate\Support\Facades\Http;

$courseMapHtml = function (string $name): string {
    return <<<HTML
        <html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><table>
            <tr>
                <td><a name="contentdata" href="sp.asp?xdurl=mp1ap/CourseDetail.asp&amp;xitem=1">{$name}</a></td>
                <td>115下</td>
                <td>選修</td>
                <td>3</td>
                <td>生活科學系</td>
                <td>四次</td>
                <td>網頁</td>
                <td></td>
                <td>專業</td>
            </tr>
        </table></body></html>
        HTML;
};

it('updates a course whose name differs only in spacing instead of duplicating it', function () use ($courseMapHtml) {
    $existing = Course::factory()->create(['name' => '測試傳記AI實務', 'term' => '2026B']);

    Http::fake(['coursemap.nou.edu.tw/*' => Http::response($courseMapHtml('測試傳記 AI 實務'), 200)]);

    $this->artisan('course:fetch-map', ['term' => '2026B'])->assertSuccessful();

    expect(Course::query()->where('term', '2026B')->count())->toBe(1);

    $existing->refresh();

    expect($existing->name)->toBe('測試傳記AI實務')
        ->and($existing->department)->toBe('生活科學系')
        ->and($existing->credits)->toBe(3)
        ->and($existing->description_url)->toContain('CourseDetail.asp');
});

it('creates the course when no name in the term matches', function () use ($courseMapHtml) {
    Course::factory()->create(['name' => '測試傳記AI實務', 'term' => '2026A']);

    Http::fake(['coursemap.nou.edu.tw/*' => Http::response($courseMapHtml('測試傳記 AI 實務'), 200)]);

    $this->artisan('course:fetch-map', ['term' => '2026B'])->assertSuccessful();

    expect(Course::query()->where('term', '2026B')->pluck('name')->all())->toBe(['測試傳記 AI 實務']);
});
