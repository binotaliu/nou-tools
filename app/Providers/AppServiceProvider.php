<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\CoursePolicy;
use App\Policies\DiscountStoreCategoryPolicy;
use App\Policies\DiscountStorePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

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
        Password::defaults(fn (): Password => Password::min(8)->uncompromised());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(DiscountStore::class, DiscountStorePolicy::class);
        Gate::policy(DiscountStoreCategory::class, DiscountStoreCategoryPolicy::class);

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
        Request::macro('studentScheduleFromCookie', function () {
            /** @var Request $this */
            return app(ReadStudentScheduleCookie::class)($this);
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
