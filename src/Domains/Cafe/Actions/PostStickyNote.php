<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\AcornTransactionKind;
use App\Enums\StickyNoteStatus;
use App\Jobs\ScreenStickyNoteJob;
use App\Models\CafeStickyNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use NouTools\Domains\Acorns\Actions\SpendAcorns;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Cafe\DataTransferObjects\PostStickyNoteData;
use NouTools\Domains\Cafe\Exceptions\StickyNoteNotAllowedException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use Throwable;

/**
 * Buys and files a sticky note. The checks and the payment share one
 * transaction under a lock on the schedule row, so two taps cannot buy two
 * notes in a day. The note starts in Screening and the AI pre-screen decides,
 * on the real queue, whether it goes straight on the wall.
 */
final readonly class PostStickyNote
{
    public function __construct(
        private SpendAcorns $spendAcorns,
        private StoreStickyNoteDrawing $storeStickyNoteDrawing,
        private CheckStickyNoteEligibility $checkStickyNoteEligibility,
    ) {}

    /**
     * @throws StickyNoteNotAllowedException
     * @throws InsufficientAcornsException
     */
    public function __invoke(StudentScheduleCookie $viewer, PostStickyNoteData $input): CafeStickyNote
    {
        $drawingPath = $input->drawing === null ? null : ($this->storeStickyNoteDrawing)($input->drawing);

        try {
            $note = DB::transaction(function () use ($viewer, $input, $drawingPath): CafeStickyNote {
                DB::table('student_schedules')->where('id', $viewer->id)->lockForUpdate()->value('id');

                ($this->checkStickyNoteEligibility)($viewer->id);

                $note = new CafeStickyNote;
                $note->student_schedule_id = $viewer->id;
                $note->text = filled($input->text) ? trim($input->text) : null;
                $note->drawing_path = $drawingPath;
                $note->paper_color = $input->paperColor;
                $note->x = $input->x;
                $note->y = $input->y;
                $note->rotation = $input->rotation;
                $note->status = StickyNoteStatus::Screening;
                $note->saveOrFail();

                $spend = ($this->spendAcorns)(
                    $viewer->id,
                    (int) config('cafe.notes.price'),
                    AcornTransactionKind::StickyNotePurchase,
                    $note,
                );

                $note->acorn_transaction_id = $spend->id;
                $note->saveOrFail();

                return $note;
            });
        } catch (Throwable $throwable) {
            if ($drawingPath !== null) {
                Storage::disk(CafeStickyNote::DRAWING_DISK)->delete($drawingPath);
            }

            throw $throwable;
        }

        ScreenStickyNoteJob::dispatch($note);

        return $note;
    }
}
