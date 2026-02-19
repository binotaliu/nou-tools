<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Date;
use Illuminate\View\Component;
use Illuminate\View\View;

class Greeting extends Component
{
    public string $greetingText;

    public string $dateString;

    public string $weekday;

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

        $weekdayMap = ['日', '一', '二', '三', '四', '五', '六'];
        $this->weekday = $weekdayMap[$now->dayOfWeek];
        $this->dateString = $now->format('Y 年 n 月 j 日');

        // semester display (e.g. 2025B -> "114 下學期")
        $semesterCode = config('app.current_semester');
        $semesterLabel = $semesterCode;

        if (preg_match('/^(\d{4})([ABC])$/', (string) $semesterCode, $m)) {
            $rocYear = (int) $m[1] - 1911;
            $termMap = ['A' => '上學期', 'B' => '下學期', 'C' => '暑期'];
            $termName = $termMap[$m[2]] ?? $m[2];
            $semesterLabel = "{$rocYear} {$termName}";
        }

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
                $diffDays = $today->diffInDays($start);
                $weekNum = intdiv($diffDays, 7) + 1;
                $semesterInfo = "{$semesterLabel}第".$this->toChinese($weekNum).'週';
            }
        }

        $this->semesterInfo = $semesterInfo;
    }

    private function toChinese(int $n): string
    {
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
    }

    public function render(): View
    {
        return view('components.greeting');
    }
}
