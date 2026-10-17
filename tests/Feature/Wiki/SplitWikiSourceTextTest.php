<?php

declare(strict_types=1);

use NouTools\Domains\Wiki\Actions\SplitWikiSourceText;

$splitPages = fn (array $pages): array => app(SplitWikiSourceText::class)($pages);

it('splits numbered items in sequence and appendices on their own line', function () use ($splitPages) {
    $sections = $splitPages([
        "目次\n第 1 頁 選課注意事項",
        "一、註冊方式：\n請臨櫃辦理。\n二、選課日期：8 月 1 日起。",
        "三、教學方式：\n視訊面授。\n【附錄一】\n課程開設科目表\n甲課程",
        "乙課程\n【附錄二】 考試時間表\n期中考 11 月",
    ]);

    expect(array_column($sections, 'key'))->toBe(['preface', 'part-1', 'part-2', 'part-3', 'appendix-1', 'appendix-2'])
        ->and($sections[1]['title'])->toBe('一、註冊方式')
        ->and($sections[1]['body'])->toBe("一、註冊方式：\n請臨櫃辦理。")
        ->and($sections[2])->toMatchArray(['title' => '二、選課日期', 'page_start' => 2, 'page_end' => 2])
        ->and($sections[4])->toMatchArray(['title' => '附錄一 課程開設科目表', 'page_start' => 3, 'page_end' => 4])
        ->and($sections[5]['title'])->toBe('附錄二 考試時間表');
});

it('ignores numbered lists inside appendices and out-of-order numerals', function () use ($splitPages) {
    $sections = $splitPages([
        "一、註冊方式：\n內容\n二、日期：\n內容\n一、不是標題（順序不對）",
        "【附錄一】\n科目表\n一、備註欄\n二、另一項",
    ]);

    expect(array_column($sections, 'key'))->toBe(['part-1', 'part-2', 'appendix-1'])
        ->and($sections[1]['body'])->toContain('一、不是標題')
        ->and($sections[2]['body'])->toContain('二、另一項');
});

it('reads composite numerals such as 十四, 廿 and 廿四', function () use ($splitPages) {
    $lines = [];

    foreach (['一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二', '十三', '十四'] as $numeral) {
        $lines[] = $numeral.'、項目';
    }

    $sections = $splitPages([implode("\n", $lines), "【附錄廿】\n標題\n【附錄廿四】\n標題二"]);

    expect(array_column($sections, 'key'))->toContain('part-14', 'appendix-20', 'appendix-24');
});

it('closes the spacing between Han characters in a heading', function () use ($splitPages) {
    $sections = $splitPages(["一、甲：\n內容\n二、乙：\n內容", "【附錄一】\n國 立 空 中 大 學 時 間 表\n內容"]);

    expect($sections[2]['title'])->toBe('附錄一 國立空中大學時間表');
});

it('cuts a long section at page boundaries', function () use ($splitPages) {
    $page = str_repeat("長長的一行文字內容\n", 1500);

    $sections = $splitPages(["一、甲：\n內容\n二、乙：\n內容", "【附錄一】\n標題\n".$page, $page, $page]);
    $appendix = array_values(array_filter($sections, fn (array $section): bool => str_starts_with($section['key'], 'appendix-1')));

    expect($appendix)->toHaveCount(3)
        ->and(array_column($appendix, 'key'))->toBe(['appendix-1', 'appendix-1-2', 'appendix-1-3'])
        ->and($appendix[1]['title'])->toContain('（第 3–3 頁）')
        ->and($appendix[1])->toMatchArray(['page_start' => 3, 'page_end' => 3]);
});

it('falls back to chunks of pages when there are no headings', function () use ($splitPages) {
    $pages = array_map(fn (int $number): string => "第 {$number} 頁的內容", range(1, 7));

    $sections = $splitPages($pages);

    expect(array_column($sections, 'key'))->toBe(['pages-1-5', 'pages-6-7'])
        ->and($sections[1])->toMatchArray(['title' => '第 6–7 頁', 'page_start' => 6, 'page_end' => 7])
        ->and($sections[0]['body'])->toContain('第 5 頁的內容');
});

it('drops the empty page after the last form feed and keeps keys unique', function () use ($splitPages) {
    $sections = $splitPages(["【附錄一】\n甲\n【附錄一】\n乙", '']);

    expect(array_column($sections, 'key'))->toBe(['appendix-1', 'appendix-1-dup2']);
});
