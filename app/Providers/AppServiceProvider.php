<?php

namespace App\Providers;

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

            return "{$rocYear}學年度{$termName}";
        });
    }
}
