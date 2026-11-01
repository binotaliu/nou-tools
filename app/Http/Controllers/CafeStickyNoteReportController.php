<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CafeStickyNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\Actions\ReportStickyNote;

final class CafeStickyNoteReportController extends Controller
{
    public function __invoke(Request $request, CafeStickyNote $note, ReportStickyNote $reportStickyNote): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'reported' => $reportStickyNote($note, $request->session()->getId()),
        ]);
    }
}
