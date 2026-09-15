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
}
