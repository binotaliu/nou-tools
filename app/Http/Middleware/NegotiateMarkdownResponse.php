<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NegotiateMarkdownResponse
{
    /**
     * Handle an incoming request, dispatching to the given markdown controller
     * whenever the client's Accept header prefers markdown.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  class-string  $markdownController
     */
    public function handle(Request $request, Closure $next, string $markdownController): Response
    {
        $response = $request->wantsMarkdown()
            ? app()->call([app($markdownController), '__invoke'], $request->route()->parameters())
            : $next($request);

        $response->headers->set('Vary', 'Accept');

        return $response;
    }
}
