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
                '今日讀書效率高，適合挑戰最難的部分。',
                '記憶力特別清晰的一天，只要專心就能記得教材內容。',
                '思路清晰，若有作業還沒寫可以考慮今天寫完。',
            ],
            'mid' => [
                '按照進度按部就班即可平穩度過一天。',
                '照著計畫，會是平凡踏實的一天。',
                '今天只要從簡單的部分開始讀的話，就比較容易進入狀態。',
            ],
            'low' => [
                '今天要小心不要分心，可以善用蕃茄鐘工作法。',
                '今天可以複習一下之前還不熟的內容，避免遺忘。',
                '讀書時最好把手機放遠一點，避免分心。',
            ],
        ],
        'love' => [
            'high' => [
                '人際運旺，主動打招呼會有意外的收穫。',
                '可以考慮和同學、朋友聊聊近況。',
                '桃花與人緣都不錯。跟朋友聊天可以分享自己的好運。',
            ],
            'mid' => [
                '感情平順，珍惜身邊熟悉的人。',
                '不刻意製造話題，自然相處即可。',
                '今天適合跟家人或朋友喝杯飲料聊聊天。',
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
                '收支平穩，注意預算即可。',
                '不太會有意外之財，但也不會有意外之支。',
                '若有想買的東西，可以先放進清單，明天再決定。',
            ],
            'low' => [
                '要小心衝動購物，下單前先深呼吸。',
                '荷包容易縮水，盡量避免買飲料或零食。',
                '應避免借貸或代墊。',
            ],
        ],
        'health' => [
            'high' => [
                '精力充沛，適合動一動或散散步。',
                '身體狀態很好，睡前伸展一下會更棒。',
                '今天會是充滿元氣的一天，若有熬夜或疲憊都可以快速恢復。',
            ],
            'mid' => [
                '狀態普通，記得多喝水、按時吃飯。',
                '保持規律作息，即可維持良好健康。',
                '每讀書或工作一小時，最好起身伸展一下。',
            ],
            'low' => [
                '容易疲倦，別硬撐，記得早點休息。',
                '肩頸和眼睛都是需要休息的一天。今天少看點螢幕吧。',
                '今天最好少喝咖啡，多喝水。',
            ],
        ],
    ];
}
