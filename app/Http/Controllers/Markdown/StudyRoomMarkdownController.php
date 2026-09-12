<?php

declare(strict_types=1);

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use App\Settings\StudyRoomSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use NouTools\Domains\StudyRoom\Actions\ShowStudyRoomPage;

final class StudyRoomMarkdownController extends Controller
{
    public function __invoke(Request $request, ShowStudyRoomPage $showStudyRoomPage, StudyRoomSettings $studyRoomSettings): Response
    {
        return response()
            ->view('study-room.markdown.show', [
                'viewModel' => $showStudyRoomPage($request),
                'announcementMarkdown' => $studyRoomSettings->announcement,
            ])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
