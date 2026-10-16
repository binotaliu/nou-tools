<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use NouTools\Domains\Wiki\Ai\WikiEditorDraft;
use NouTools\Domains\Wiki\Exceptions\InvalidWikiDraftException;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use Throwable;

/**
 * Checks an applied draft against the rules the server enforces whatever the
 * model was told, before anything is saved.
 */
final readonly class ValidateWikiDraft
{
    private const int MAX_DESCRIPTION_LENGTH = 300;

    private const int MAX_EDIT_SUMMARY_LENGTH = 255;

    private const int MAX_CATEGORY_LENGTH = 40;

    public function __construct(
        private InspectWikiMarkdown $inspectWikiMarkdown,
        private RenderWikiMarkdown $renderWikiMarkdown,
    ) {}

    /**
     * @throws InvalidWikiDraftException
     */
    public function __invoke(WikiEditRequest $editRequest, WikiEditorDraft $draft): void
    {
        $problems = [];

        if ($draft->title === '' || mb_strlen($draft->title) > (int) config('wiki.max_title_length')) {
            $problems[] = 'title is empty or too long';
        }

        if (mb_strlen($draft->description) > self::MAX_DESCRIPTION_LENGTH) {
            $problems[] = 'description is too long';
        }

        if (trim($draft->body) === '' || mb_strlen($draft->body) > (int) config('wiki.max_body_chars')) {
            $problems[] = 'body is empty or too long';
        }

        if ($draft->editSummary === '' || mb_strlen($draft->editSummary) > self::MAX_EDIT_SUMMARY_LENGTH) {
            $problems[] = 'edit summary is empty or too long';
        }

        if ($draft->categories === [] || count($draft->categories) > (int) config('wiki.max_categories')) {
            $problems[] = 'categories must number between 1 and '.config('wiki.max_categories');
        }

        foreach ($draft->categories as $category) {
            if (mb_strlen($category) > self::MAX_CATEGORY_LENGTH) {
                $problems[] = "category \"{$category}\" is too long";
            }
        }

        if ($editRequest->isNewPage() && ! WikiPage::isSlugAvailable($draft->slug)) {
            $problems[] = "slug \"{$draft->slug}\" is invalid or taken";
        }

        try {
            ($this->renderWikiMarkdown)($draft->body);
            $externalUrls = ($this->inspectWikiMarkdown)($draft->body)['externalUrls'];
        } catch (Throwable $exception) {
            $problems[] = 'body does not render: '.$exception->getMessage();
            $externalUrls = [];
        }

        $baseUrls = $editRequest->baseRevision === null
            ? []
            : ($this->inspectWikiMarkdown)($editRequest->baseRevision->body)['externalUrls'];

        foreach ($externalUrls as $url) {
            if (! WikiSourceDomains::allows($url) && ! in_array($url, $baseUrls, true)) {
                $problems[] = "link to a host outside the allowed sources: {$url}";
            }
        }

        if ($problems !== []) {
            throw new InvalidWikiDraftException($problems);
        }
    }
}
