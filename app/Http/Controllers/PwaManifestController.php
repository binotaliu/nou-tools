<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PwaManifestController extends Controller
{
    /**
     * Builds the web app manifest per-request so the installed PWA can open
     * straight to the schedule it was installed from (via ?schedule=<token>,
     * the same short token used in share links) instead of always landing on
     * the homepage. The `id` is pinned so an install from any page is
     * recognized as the same app, even though `start_url` varies.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $scheduleToken = $request->query('schedule');

        $schedule = is_string($scheduleToken)
            ? (new StudentSchedule)->resolveRouteBinding($scheduleToken)
            : null;

        $startUrl = $schedule instanceof StudentSchedule
            ? route('schedules.show', $schedule, absolute: false)
            : route('schedules.my', absolute: false);

        return response()->json([
            'id' => '/',
            'name' => 'NOU 小幫手',
            'short_name' => 'NOU 小幫手',
            'description' => '給 NOU 同學的非官方小工具：管理個人課表與學習進度',
            'start_url' => $startUrl.'?utm_source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#fff6f3',
            'theme_color' => '#b05139',
            'lang' => 'zh-Hant',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
