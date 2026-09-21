<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NouTools\Domains\Schedules\Actions\BuildSchedulePrintPage;
use NouTools\Domains\Schedules\Actions\RenderSchedulePdf;

final class SchedulePrintController extends Controller
{
    public function show(StudentSchedule $schedule, Request $request, BuildSchedulePrintPage $buildSchedulePrintPage): View
    {
        return view('schedule.print', [
            'page' => $buildSchedulePrintPage($schedule, $request->query('term')),
            'inlineAssets' => false,
        ]);
    }

    public function pdf(StudentSchedule $schedule, Request $request, BuildSchedulePrintPage $buildSchedulePrintPage, RenderSchedulePdf $renderSchedulePdf): Response
    {
        $html = view('schedule.print', [
            'page' => $buildSchedulePrintPage($schedule, $request->query('term')),
            'inlineAssets' => true,
        ])->render();

        return response($renderSchedulePdf($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="nou-schedule.pdf"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
