<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\LearningProgress\Actions\RedirectToMyLearningProgress;

final class ScheduleMyLearningProgressController extends Controller
{
    public function __invoke(Request $request, RedirectToMyLearningProgress $redirectToMyLearningProgress): RedirectResponse
    {
        return $redirectToMyLearningProgress($request);
    }
}
