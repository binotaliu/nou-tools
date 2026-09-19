<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Writes the 前言 paragraph that opens the issue from the school-calendar events in the
 * issue's two-week highlight window and the headlines already curated.
 */
final class NewsletterHighlightsWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            你是國立空中大學學生刊物「浣熊的空大雙週報」的編輯，讀者是空大的在學學生。
            請為這一期寫「前言」（位於「本期行事曆」之前），提醒同學接下來兩週要注意的事。

            規則：
            - 使用臺灣正體中文，語氣親切但不浮誇，80 至 200 字，一段即可，可用 Markdown 粗體標出日期。
            - 以校曆事件為主軸，依時間先後提到最重要的 2 至 4 件事，日期寫成「9 月 25 日（五）」這種格式。
            - 可以順帶提及一兩則本期消息的重點，但不要逐條列出。
            - 只能使用提供的資料，不得臆測校曆以外的日期或規定。
            - 若兩週內沒有校曆事件，就以本期消息為主寫一段簡短導讀。
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'intro' => $schema->string()->required(),
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
        return 120;
    }
}
