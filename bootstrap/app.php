<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NegotiateMarkdownResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Csp\AddCspHeaders;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'markdown' => NegotiateMarkdownResponse::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddCspHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Renders HTTP error responses through Inertia's shared Error page
        // (resources/js/Pages/Error.vue) instead of Laravel's per-status
        // errors.{code} Blade views (resources/views/errors/ has been
        // removed). Standard Laravel+Inertia convention: one status-driven
        // component rather than nine near-identical pages. Left alone when
        // debug mode is on so local development still gets Laravel's
        // detailed exception page.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $statusesWithErrorPage = [401, 402, 403, 404, 419, 429, 500, 503];

            if (! app()->hasDebugModeEnabled() && in_array($response->getStatusCode(), $statusesWithErrorPage, true)) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
