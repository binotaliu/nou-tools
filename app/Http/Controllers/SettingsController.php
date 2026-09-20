<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Schedules\Actions\ShowNotificationSettings;

final class SettingsController extends Controller
{
    public function __invoke(Request $request, ShowNotificationSettings $showNotificationSettings): Response
    {
        $viewer = $request->studentScheduleFromCookie();

        return Inertia::render('Settings/Show', [
            'notifications' => $viewer === null ? null : $showNotificationSettings($viewer),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ]);
    }
}
