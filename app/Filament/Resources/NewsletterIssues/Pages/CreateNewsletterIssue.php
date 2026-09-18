<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterIssues\Pages;

use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use App\Models\NewsletterIssue;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Newsletter\Actions\CreateNewsletterDraft;
use NouTools\Domains\Newsletter\Actions\ResolveNewsletterIssueSchedule;

class CreateNewsletterIssue extends CreateRecord
{
    protected static string $resource = NewsletterIssueResource::class;

    protected function fillForm(): void
    {
        $nextFreeIssue = app(ResolveNewsletterIssueSchedule::class)->nextOnOrAfter(Date::now(ResolveNewsletterIssueSchedule::TIMEZONE));

        while (NewsletterIssue::query()->where('issue_key', $nextFreeIssue->issueKey)->exists()) {
            $nextFreeIssue = app(ResolveNewsletterIssueSchedule::class)->nextOnOrAfter($nextFreeIssue->publishesOn->addDay());
        }

        $this->form->fill(['publishes_on' => $nextFreeIssue->publishesOn->toDateString()]);
    }

    /**
     * Creation goes through CreateNewsletterDraft so windows and the
     * calendar snapshot are derived the same way the scheduled draft does.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $schedule = app(ResolveNewsletterIssueSchedule::class)((string) $data['publishes_on']);

        if (NewsletterIssue::query()->where('issue_key', $schedule->issueKey)->exists()) {
            throw ValidationException::withMessages([
                'data.publishes_on' => "{$schedule->issueKey} 已經存在。",
            ]);
        }

        $issue = app(CreateNewsletterDraft::class)($schedule);
        $issue->title = $data['title'] ?? null;
        $issue->saveOrFail();

        return $issue;
    }
}
