<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\StudyRoom\Actions\ShowStudyRoomPage;

final class StudyRoomController extends Controller
{
    public function show(Request $request, ShowStudyRoomPage $showStudyRoomPage): View
    {
        return view('study-room.show', ['viewModel' => $showStudyRoomPage($request)]);
    }
}
