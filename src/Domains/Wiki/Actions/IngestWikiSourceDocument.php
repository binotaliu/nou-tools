<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NouTools\Domains\Wiki\Exceptions\WikiSourceFetchException;
use NouTools\Domains\Wiki\Support\FetchWikiSourceText;
use Throwable;

/**
 * Fetches a source document, splits it into sections and replaces the stored
 * ones. The document is `ready` afterwards, or `failed` with the reason, in
 * which case the earlier sections stay readable. Nothing is thrown: the
 * outcome is the document's status. Returns which section keys were added,
 * changed (by content hash) or removed, all empty on failure.
 */
final readonly class IngestWikiSourceDocument
{
    /** The real 選課注意事項 is about 3 MB. */
    public const int MAX_DOWNLOAD_BYTES = 15_000_000;

    public function __construct(
        private FetchWikiSourceText $fetchWikiSourceText,
        private SplitWikiSourceText $splitWikiSourceText,
    ) {}

    /**
     * @return array{added: list<string>, changed: list<string>, removed: list<string>}
     */
    public function __invoke(WikiSourceDocument $document): array
    {
        $document->status = WikiSourceDocumentStatus::Fetching;
        $document->failure_reason = null;
        $document->saveOrFail();

        try {
            $source = ($this->fetchWikiSourceText)($document->url, self::MAX_DOWNLOAD_BYTES);
            $sections = ($this->splitWikiSourceText)($source->pages);

            if ($sections === []) {
                throw new WikiSourceFetchException('文件沒有可用的文字。');
            }

            return $this->replaceSections($document, $sections, count($source->pages));
        } catch (WikiSourceFetchException $exception) {
            return $this->fail($document, $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Wiki source document ingest failed.', ['id' => $document->id, 'exception' => $exception->getMessage()]);
            report($exception);

            return $this->fail($document, '未預期的錯誤：'.Str::limit($exception->getMessage(), 200));
        }
    }

    /**
     * @param  list<array{key: string, title: string, body: string, page_start: int, page_end: int}>  $sections
     * @return array{added: list<string>, changed: list<string>, removed: list<string>}
     */
    private function replaceSections(WikiSourceDocument $document, array $sections, int $pageCount): array
    {
        return DB::transaction(function () use ($document, $sections, $pageCount): array {
            $existing = $document->sections()->get()->keyBy('key');
            $result = ['added' => [], 'changed' => [], 'removed' => []];

            foreach ($sections as $position => $data) {
                $hash = hash('sha256', $data['body']);
                $section = $existing->get($data['key']) ?? new WikiSourceSection;

                if (! $section->exists) {
                    $result['added'][] = $data['key'];
                } elseif ($section->content_hash !== $hash) {
                    $result['changed'][] = $data['key'];
                }

                $section->wiki_source_document_id = $document->id;
                $section->key = $data['key'];
                $section->title = $data['title'];
                $section->body = $data['body'];
                $section->page_start = $data['page_start'];
                $section->page_end = $data['page_end'];
                $section->position = $position;
                $section->content_hash = $hash;
                $section->saveOrFail();
            }

            $kept = array_column($sections, 'key');
            $removed = $existing->keys()->reject(fn (string $key): bool => in_array($key, $kept, true))->values();

            if ($removed->isNotEmpty()) {
                $document->sections()->whereIn('key', $removed->all())->delete();
                $result['removed'] = $removed->all();
            }

            $document->status = WikiSourceDocumentStatus::Ready;
            $document->page_count = $pageCount;
            $document->fetched_at = now();
            $document->saveOrFail();

            return $result;
        });
    }

    /**
     * @return array{added: list<string>, changed: list<string>, removed: list<string>}
     */
    private function fail(WikiSourceDocument $document, string $reason): array
    {
        $document->status = WikiSourceDocumentStatus::Failed;
        $document->failure_reason = $reason;
        $document->saveOrFail();

        return ['added' => [], 'changed' => [], 'removed' => []];
    }
}
