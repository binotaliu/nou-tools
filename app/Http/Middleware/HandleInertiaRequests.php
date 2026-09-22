<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'analyticsPage' => self::analyticsPagePath($request),
            'analyticsTitle' => self::analyticsTitle($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }

    /**
     * Mirrors resources/views/app.blade.php's `$analyticsPage`: the matched
     * route's URI with dynamic segments masked out (e.g.
     * `/schedules/{schedule}` -> `/schedules/:schedule`), so Inertia
     * navigations are grouped by route shape in GA rather than by every
     * distinct schedule/course/etc. token. The <body> tag only carries this
     * for the initial (server-rendered) visit, so subsequent client-side
     * navigations read it from here instead (see resources/js/app.js's
     * `router.on('navigate', ...)`).
     */
    private static function analyticsPagePath(Request $request): string
    {
        $route = $request->route();

        return $route
            ? '/'.ltrim((string) preg_replace('/\{(\w+)\??\}/', ':$1', $route->uri()), '/')
            : '/'.ltrim($request->path(), '/');
    }

    /**
     * A generic, route-name-keyed page title for GA's `page_title`, used
     * instead of `document.title` on schedule pages. Those pages title
     * themselves after the student's own schedule/course names (e.g. "{$name}
     * - NOU 小幫手"), which would otherwise leak into analytics verbatim.
     * Falls back to `document.title` client-side for every other route.
     */
    private static function analyticsTitle(Request $request): ?string
    {
        return match ($request->route()?->getName()) {
            'schedules.show' => '我的課表 - NOU 小幫手',
            'schedules.customize' => '自訂課表 - NOU 小幫手',
            'schedules.subscribe' => '訂閱行事曆 - NOU 小幫手',
            'learning-progress.show' => '學習進度表 - NOU 小幫手',
            default => null,
        };
    }
}
