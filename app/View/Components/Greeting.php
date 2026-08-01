<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

final class Greeting extends Component
{
    public string $semesterLabel;

    public string $semesterCode;

    public ?string $semesterStart;

    public ?string $semesterEnd;

    public function __construct()
    {
        // The greeting text, current date, and semester week are rendered on
        // the client from the viewer's local clock (see resources/js/app.js,
        // window.nouToolsGreeting), so overseas students get a greeting that
        // matches their own time and the page stays correct when served from
        // an offline cache. Here we only expose the static, timezone-independent
        // semester facts.
        $this->semesterCode = (string) config('app.current_semester');
        $this->semesterLabel = Str::toSemesterDisplay($this->semesterCode);

        $range = config('app.current_semester_range', []);
        $this->semesterStart = is_array($range) && ! empty($range[0]) ? (string) $range[0] : null;
        $this->semesterEnd = is_array($range) && ! empty($range[1]) ? (string) $range[1] : null;
    }

    public function render(): View
    {
        return view('components.greeting');
    }
}
