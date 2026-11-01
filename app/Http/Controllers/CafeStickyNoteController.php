<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Cafe\Actions\DescribeStickyNoteEligibility;
use NouTools\Domains\Cafe\Actions\ListStickyNotes;
use NouTools\Domains\Cafe\Actions\PostStickyNote;
use NouTools\Domains\Cafe\Actions\ResolveCafeViewer;
use NouTools\Domains\Cafe\DataTransferObjects\PostStickyNoteData;
use NouTools\Domains\Cafe\Exceptions\StickyNoteNotAllowedException;
use NouTools\Domains\Cafe\ViewModels\StickyNoteViewModel;

final class CafeStickyNoteController extends Controller
{
    public function index(Request $request, ListStickyNotes $listStickyNotes, DescribeStickyNoteEligibility $describeStickyNoteEligibility): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        return response()->json([
            'notes' => $listStickyNotes($viewer),
            'blockedReason' => $viewer === null ? null : $describeStickyNoteEligibility($viewer->id),
        ]);
    }

    public function store(PostStickyNoteData $input, Request $request, ResolveCafeViewer $resolveCafeViewer, PostStickyNote $postStickyNote, ReadAcornBalance $readAcornBalance): JsonResponse
    {
        $viewer = $resolveCafeViewer($request);

        if ($viewer === null) {
            return response()->json(['message' => '請先至自習室設定暱稱，方可使用便利貼。'], 403);
        }

        try {
            $note = $postStickyNote($viewer, $input);
        } catch (StickyNoteNotAllowedException|InsufficientAcornsException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'note' => StickyNoteViewModel::fromModel($note->load('schedule.studyRoomProfile'), $viewer->id),
            'acorns' => $readAcornBalance($viewer->id),
        ], 201);
    }
}
