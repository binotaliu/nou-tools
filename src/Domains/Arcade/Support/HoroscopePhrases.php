<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\Support;

/**
 * The words behind the horoscope: aspect and ruler names, the lucky pools and
 * the advice sentences. Pure data, kept apart from the algorithm so the
 * wording can be tuned without touching it. Changing a pool changes which
 * thing a given seed picks, so only do it between days, never mid-day.
 */
final class HoroscopePhrases
{
    /**
     * The four aspects that are calculated; 整體運 is derived from them.
     *
     * @var array<int, string>
     */
    public const CORE_ASPECTS = ['study', 'love', 'wealth', 'health'];

    /**
     * @var array<string, string>
     */
    public const ASPECT_LABELS = [
        'overall' => '整體運',
        'study' => '學業運',
        'love' => '愛情運',
        'wealth' => '財運',
        'health' => '健康運',
    ];

    /**
     * Ruled weekday by Carbon's dayOfWeek (0 = Sunday).
     *
     * @var array<int, string>
     */
    public const DAY_RULERS = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'];

    /**
     * @var array<string, string>
     */
    public const RULER_LABELS = [
        'sun' => '太陽',
        'moon' => '月亮',
        'mars' => '火星',
        'mercury' => '水星',
        'jupiter' => '木星',
        'venus' => '金星',
        'saturn' => '土星',
    ];

    /**
     * @var array<int, array{name: string, hex: string}>
     */
    public const COLORS = [
        ['name' => '琥珀橘', 'hex' => '#d9822b'],
        ['name' => '奶油黃', 'hex' => '#f2d98b'],
        ['name' => '薄荷綠', 'hex' => '#7cc9a4'],
        ['name' => '森林綠', 'hex' => '#3f7a4f'],
        ['name' => '天空藍', 'hex' => '#6db3e8'],
        ['name' => '海軍藍', 'hex' => '#2b3f6b'],
        ['name' => '薰衣草紫', 'hex' => '#a58bd6'],
        ['name' => '莓果紅', 'hex' => '#b8325a'],
        ['name' => '櫻花粉', 'hex' => '#f2a7b8'],
        ['name' => '焦糖棕', 'hex' => '#8a5a36'],
        ['name' => '雲朵白', 'hex' => '#f4f1ea'],
        ['name' => '石墨灰', 'hex' => '#5d6670'],
    ];

    /**
     * @var array<int, string>
     */
    public const ITEMS = [
        '螢光筆', '便利貼', '拿鐵', '耳機', '保溫瓶', '手帳', '橡皮擦', '計時器',
        '帆布袋', '薄荷糖', '舊筆記', '藍筆', '書籤', '小盆栽', '眼鏡布', '隨身碟',
    ];

    /**
     * @var array<int, string>
     */
    public const DIRECTIONS = ['東', '東南', '南', '西南', '西', '西北', '北', '東北'];

    /**
     * Advice by aspect and tier. "high" and "mid" are drawn for the strongest
     * aspect, "mid" and "low" for the weakest.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    public const ADVICE = [
        'study' => [
            'high' => [
                '讀書效率很高，適合挑戰最難的那個單元。',
                '今天的記憶力特別好，影片課和教科書都能一次進腦。',
                '思路清晰，趁勢把拖延的作業寫完吧。',
            ],
            'mid' => [
                '進度穩穩前進就好，不必和別人比快慢。',
                '照著計畫走，今天會是平凡但踏實的一天。',
                '先從最短的章節開始，慢慢進入狀態。',
            ],
            'low' => [
                '專注力容易飄走，把大目標切成二十五分鐘的小段落。',
                '今天不適合硬啃新內容，複習舊筆記更划算。',
                '手機放遠一點，先做完一件小事再說。',
            ],
        ],
        'love' => [
            'high' => [
                '人際運旺，主動打招呼會有意外的收穫。',
                '適合和同學、朋友聊聊近況，關係會更靠近。',
                '桃花與人緣都不錯，記得把好心情分享出去。',
            ],
            'mid' => [
                '感情平順，珍惜身邊熟悉的人就很好。',
                '不用刻意製造話題，自然相處最舒服。',
                '和家人或朋友喝杯飲料，簡單就是幸福。',
            ],
            'low' => [
                '說話前多想一秒，避免無心的誤會。',
                '今天容易感到孤單，找個人通通電話吧。',
                '別急著下結論，給彼此一點空間。',
            ],
        ],
        'wealth' => [
            'high' => [
                '財運不錯，適合買一直想要的文具或書。',
                '小有收穫的一天，說不定會遇到折扣或優惠。',
                '理財直覺靈敏，把這個月的花費整理一下吧。',
            ],
            'mid' => [
                '收支平穩，按預算花就沒問題。',
                '不太會有意外之財，也不會有意外之支。',
                '想買的東西先放進清單，隔天再決定。',
            ],
            'low' => [
                '衝動消費要小心，下單前先深呼吸。',
                '荷包容易縮水，今天盡量自己帶水和點心。',
                '避免借貸或代墊，免得之後麻煩。',
            ],
        ],
        'health' => [
            'high' => [
                '精力充沛，適合動一動或散散步。',
                '身體狀態很好，睡前伸展一下會更棒。',
                '元氣滿滿，連熬夜的疲憊都恢復得特別快。',
            ],
            'mid' => [
                '狀態普通，記得多喝水、按時吃飯。',
                '保持規律作息，就是最好的保養。',
                '每讀一小時就起身走動五分鐘。',
            ],
            'low' => [
                '容易疲倦，別硬撐，早點睡最實在。',
                '肩頸和眼睛需要休息，讓螢幕暫停一下。',
                '今天少喝點咖啡，多補充水分。',
            ],
        ],
    ];
}
