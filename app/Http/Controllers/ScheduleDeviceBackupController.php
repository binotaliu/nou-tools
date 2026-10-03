<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

/**
 * Hands a remembered device its own token so the page can keep a copy in local
 * storage. The cookie is HttpOnly, so script cannot read it; this is the only
 * place the token leaves it, and it answers 204 for a session-only device.
 */
final class ScheduleDeviceBackupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->studentScheduleFromCookie();

        $state = $request->attributes->get(ReadStudentScheduleCookie::DEVICE_STATE_ATTRIBUTE);
        $token = $request->cookie(BuildScheduleDeviceCookie::NAME)
            ?? $request->attributes->get(ReadStudentScheduleCookie::MINTED_TOKEN_ATTRIBUTE);

        if (! is_array($state) || ! $state['persistent'] || ! is_string($token)) {
            return response()->json(null, 204);
        }

        return response()->json(['token' => $token, 'fingerprint' => $state['fingerprint']]);
    }
}
