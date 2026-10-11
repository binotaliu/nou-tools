<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\GradeCalculator\Actions\RedirectToMyGradeCalculator;

final class ScheduleMyGradeCalculatorController extends Controller
{
    public function __invoke(Request $request, RedirectToMyGradeCalculator $redirectToMyGradeCalculator): RedirectResponse
    {
        return $redirectToMyGradeCalculator($request);
    }
}
