<?php

namespace App\Providers;

use App\Data\StudentScheduleCookie;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CarbonImmutable::setLocale(config('app.locale'));
        Date::use(CarbonImmutable::class);

        // Register a Str macro to format semester codes (always full format).
        Str::macro('toSemesterDisplay', function (string $semester): string {
            if (! preg_match('/^(\d{4})([ABC])$/', (string) $semester, $m)) {
                return $semester;
            }

            $year = (int) $m[1];
            $termCode = $m[2];
            $rocYear = $year - 1911;

            $termName = match ($termCode) {
                'A' => '上學期',
                'B' => '下學期',
                'C' => '暑期',
                default => $termCode,
            };

            return "{$rocYear} 學年度{$termName}";
        });

        // Request macro: parse the encrypted `student_schedule` cookie and
        // return a `StudentScheduleCookie` data object when valid.
        Request::macro('studentScheduleFromCookie', function (): ?StudentScheduleCookie {
            /** @var \Illuminate\Http\Request $this */
            $cookie = $this->cookie('student_schedule');
            if (! $cookie) {
                return null;
            }

            $data = json_decode($cookie, true);
            if (! is_array($data) || ! isset($data['id'], $data['uuid'])) {
                return null;
            }

            $model = \App\Models\StudentSchedule::find($data['id']);
            if (! $model) {
                return null;
            }

            return StudentScheduleCookie::fromModel($model);
        });

        Str::macro('toChineseNumber', function (int $n): string {
            if ($n > 99) {
                return (string) " {$n} "; // Fallback to digits for large numbers
            }

            $digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];

            if ($n <= 10) {
                return $n === 10 ? '十' : $digits[$n];
            }

            if ($n < 20) {
                return '十'.($n % 10 ? $digits[$n % 10] : '');
            }

            $tens = intdiv($n, 10);
            $ones = $n % 10;
            $res = ($tens == 1 ? '十' : $digits[$tens].'十').($ones ? $digits[$ones] : '');

            return $res;
        });
    }
}
