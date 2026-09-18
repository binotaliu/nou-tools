<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Ai;

use App\Enums\NewsletterSection;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Picks the announcements worth a student's attention for one newsletter
 * section and writes a headline + short summary for each. It only ever
 * sees announcement metadata (source, category, title, date), never the
 * linked page, so it must not invent details the title doesn't state.
 */
final class NewsletterItemCurator implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly NewsletterSection $section,
    ) {}

    public function instructions(): Stringable|string
    {
        $maxItems = (int) config('newsletter.ai.max_items_per_section');

        $sectionGuidance = match ($this->section) {
            NewsletterSection::News => '這一欄是「空大新消息」，來源是學校各處室與各學系。優先挑選影響大多數同學的事項：選課、考試、學費與減免、畢業、獎助學金、重要活動與制度變更。',
            NewsletterSection::Centers => '這一欄是「各中心消息」，來源是各地學習指導中心。每則都要保留中心名稱。優先挑選同學可以參加或需要處理的事項：講座、社團與聯誼活動、面授與考試相關的在地安排、服務時間異動。',
        };

        return <<<PROMPT
            你是國立空中大學學生刊物「浣熊的空大雙週報」的編輯，讀者是空大的在學學生。
            {$sectionGuidance}

            規則：
            - 使用臺灣正體中文，語氣中立、清楚，像學生報紙的短訊。
            - 最多挑選 {$maxItems} 則；寧缺勿濫，略過重複、過期或只對極少數人有意義的公告。
            - 同一件事有多則公告時只保留一則，選資訊最完整的那則。
            - headline：15 至 30 字，直接說出這則消息是什麼。
            - summary：1 至 2 句，說明誰需要注意、要做什麼。只能根據提供的標題、來源、分類與日期撰寫；標題沒有寫明的日期、金額、地點或條件一律不得臆測。
            - announcement_id 必須是輸入清單中的 id，不可自行編造。
            - 依重要性由高到低排序。
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'items' => $schema->array()
                ->items($schema->object([
                    'announcement_id' => $schema->integer()->required(),
                    'headline' => $schema->string()->required(),
                    'summary' => $schema->string()->required(),
                ])->withoutAdditionalProperties())
                ->required(),
        ];
    }

    public function provider(): string
    {
        return (string) config('newsletter.ai.provider');
    }

    public function model(): string
    {
        return (string) config('newsletter.ai.model');
    }

    public function timeout(): int
    {
        return 180;
    }
}
