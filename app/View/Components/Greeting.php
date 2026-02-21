<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class Greeting extends Component
{
    public string $greetingText;

    public string $dateString;

    public string $semesterInfo;

    public function __construct()
    {
        $now = Date::now('Asia/Taipei');
        $hour = (int) $now->format('H');

        if ($hour >= 5 && $hour < 12) {
            $this->greetingText = '早安';
        } elseif ($hour >= 12 && $hour < 18) {
            $this->greetingText = '午安';
        } else {
            $this->greetingText = '晚安';
        }

        $this->dateString = $now->isoFormat('Y 年 M 月 D 日 (dd)');

        // semester display (e.g. 2025B -> "114學年度下學期")
        $semesterCode = config('app.current_semester');
        $semesterLabel = \Illuminate\Support\Str::toSemesterDisplay((string) $semesterCode);

        $range = config('app.current_semester_range', []);
        $semesterInfo = $semesterCode;

        if (is_array($range) && count($range) === 2 && $range[0] && $range[1]) {
            $start = Date::parse($range[0], 'Asia/Taipei')->startOfDay();
            $end = Date::parse($range[1], 'Asia/Taipei')->endOfDay();
            $today = $now->copy()->startOfDay();

            if ($today->lt($start)) {
                $semesterInfo = "{$semesterLabel}尚未開始";
            } elseif ($today->gt($end)) {
                $semesterInfo = "{$semesterLabel}已結束";
            } else {
                $diffDays = $today->diffInDays($start, absolute: true);
                $weekNum = intdiv($diffDays, 7) + 1;
                $semesterInfo = "{$semesterLabel}第".Str::toChineseNumber($weekNum).'週';
            }
        }

        $this->semesterInfo = $semesterInfo;
    }

    public function render(): View
    {
        return view('components.greeting');
    }
}
