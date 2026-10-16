<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WikiEditRequest;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\Wiki\Actions\AbandonWikiEditRequest;
use NouTools\Domains\Wiki\Actions\CreateWikiEditRequest;
use NouTools\Domains\Wiki\Actions\FindWikiPageBySlug;
use NouTools\Domains\Wiki\Actions\ShowWikiEditRequestForm;
use NouTools\Domains\Wiki\Actions\ShowWikiEditRequestPage;
use NouTools\Domains\Wiki\DataTransferObjects\CreateWikiEditRequestDTO;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

final class WikiEditRequestController extends Controller
{
    public function create(Request $request, ShowWikiEditRequestForm $showWikiEditRequestForm): Response
    {
        return Inertia::render('Wiki/EditRequest/Create', [
            'viewModel' => $showWikiEditRequestForm(
                null,
                $request->studentScheduleFromCookie(),
                is_string($request->query('slug')) ? $request->query('slug') : null,
                is_string($request->query('title')) ? $request->query('title') : null,
            ),
        ]);
    }

    public function edit(Request $request, string $slug, FindWikiPageBySlug $findWikiPageBySlug, ShowWikiEditRequestForm $showWikiEditRequestForm): Response|RedirectResponse
    {
        $page = $findWikiPageBySlug($slug);

        abort_if($page === null, 404);

        if ($page->slug !== $slug) {
            return redirect()->route('wiki.edit', $page->slug, 301);
        }

        return Inertia::render('Wiki/EditRequest/Create', [
            'viewModel' => $showWikiEditRequestForm($page, $request->studentScheduleFromCookie()),
        ]);
    }

    public function store(Request $request, CreateWikiEditRequest $createWikiEditRequest): RedirectResponse
    {
        $request->validate([
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ]);

        $data = CreateWikiEditRequestDTO::validateAndCreate($request->all());

        try {
            $editRequest = $createWikiEditRequest($data, $request->studentScheduleFromCookie(), $request->ip());
        } catch (WikiEditingNotAllowedException|WikiDraftLimitReachedException $exception) {
            throw ValidationException::withMessages(['instruction' => $exception->getMessage()]);
        }

        return redirect()->route('wiki.edit-requests.show', $editRequest);
    }

    public function show(Request $request, WikiEditRequest $editRequest, ShowWikiEditRequestPage $showWikiEditRequestPage): Response
    {
        return Inertia::render('Wiki/EditRequest/Show', [
            'viewModel' => $showWikiEditRequestPage($editRequest, $request->studentScheduleFromCookie()),
        ]);
    }

    public function destroy(Request $request, WikiEditRequest $editRequest, AbandonWikiEditRequest $abandonWikiEditRequest): RedirectResponse
    {
        $this->authorizeOwner($request, $editRequest);

        try {
            $abandonWikiEditRequest($editRequest);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['request' => $exception->getMessage()]);
        }

        return $editRequest->page !== null
            ? redirect()->route('wiki.show', $editRequest->page->slug)
            : redirect()->route('wiki.index');
    }

    /**
     * Anyone with the link may watch a request, only the device's schedule
     * that made it may act on it.
     */
    private function authorizeOwner(Request $request, WikiEditRequest $editRequest): void
    {
        abort_unless($editRequest->belongsToSchedule($request->studentScheduleFromCookie()), 403);
    }
}
