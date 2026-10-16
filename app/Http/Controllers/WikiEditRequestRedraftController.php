<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WikiEditRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Wiki\Actions\RedraftWikiEditRequest;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

final class WikiEditRequestRedraftController extends Controller
{
    public function __invoke(Request $request, WikiEditRequest $editRequest, RedraftWikiEditRequest $redraftWikiEditRequest): RedirectResponse
    {
        $this->authorizeOwner($request, $editRequest);

        try {
            $redraftWikiEditRequest($editRequest, $request->studentScheduleFromCookie());
        } catch (DomainException|WikiEditingNotAllowedException|WikiDraftLimitReachedException $exception) {
            throw ValidationException::withMessages(['request' => $exception->getMessage()]);
        }

        return redirect()->route('wiki.edit-requests.show', $editRequest);
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
