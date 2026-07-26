<?php

use App\Enums\CenterRegion;
use App\Enums\LinkGroup;

return [
    'links' => [
        // region 各處室
        'school-homepage' => [
            'name' => '學校首頁',
            'url' => 'https://www.nou.edu.tw',
            'group' => LinkGroup::Administrative->value,
        ],
        'academic-affairs' => [
            'name' => '教務處',
            'url' => 'https://studadm.nou.edu.tw',
            'group' => LinkGroup::Administrative->value,
        ],
        'student-affairs' => [
            'name' => '學務處',
            'url' => 'https://www2.nou.edu.tw/coach/index.aspx',
            'group' => LinkGroup::Administrative->value,
        ],
        'publishing-center' => [
            'name' => '出版中心',
            'url' => 'https://www2.nou.edu.tw/pd/index.aspx',
            'group' => LinkGroup::Administrative->value,
        ],
        'library' => [
            'name' => '圖書館',
            'url' => 'https://library.nou.edu.tw/wSite/mp',
            'group' => LinkGroup::Administrative->value,
        ],
        'continuing-education' => [
            'name' => '推廣教育處',
            'url' => 'https://www2.nou.edu.tw/myec/index.aspx',
            'group' => LinkGroup::Administrative->value,
        ],
        'international-affairs' => [
            'name' => '國際事務處',
            'url' => 'https://www2.nou.edu.tw/oia/index.aspx',
            'group' => LinkGroup::Administrative->value,
        ],
        // endregion

        // region 學系
        'humanity-department' => [
            'name' => '人文學系',
            'url' => 'https://www2.nou.edu.tw/humanity/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        'social-sciences-department' => [
            'name' => '社會科學系',
            'url' => 'https://www2.nou.edu.tw/social/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        'business-department' => [
            'name' => '商學系',
            'url' => 'https://www2.nou.edu.tw/business/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        'public-administration-department' => [
            'name' => '公共行政學系',
            'url' => 'https://www2.nou.edu.tw/pa/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        'living-sciences-department' => [
            'name' => '生活科學系',
            'url' => 'https://www2.nou.edu.tw/living/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        'management-and-information-department' => [
            'name' => '管理與資訊學系',
            'url' => 'https://www2.nou.edu.tw/mi/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        'liberal-education-center' => [
            'name' => '通識博雅教育中心',
            'url' => 'https://www2.nou.edu.tw/nouod/index.aspx',
            'group' => LinkGroup::Department->value,
        ],
        // endregion

        // region 服務
        'video-conference' => [
            'name' => '視訊面授',
            'url' => 'https://vc.nou.edu.tw/',
            'group' => LinkGroup::Services->value,
        ],
        'uu-platform' => [
            'name' => '數位學習平台 (UU平台)',
            'url' => 'https://uu.nou.edu.tw/mooc/index.php',
            'group' => LinkGroup::Services->value,
        ],
        'academic-affairs-system' => [
            'name' => '教務行政資訊系統',
            'url' => 'https://noustud.nou.edu.tw/',
            'group' => LinkGroup::Services->value,
        ],
        'coursemap' => [
            'name' => '全校課程地圖查詢',
            'url' => 'https://coursemap.nou.edu.tw/',
            'group' => LinkGroup::Services->value,
        ],
        'textbook-edition-search' => [
            'name' => '教科書版次查詢',
            'url' => 'https://textbook.nou.edu.tw/',
            'group' => LinkGroup::Services->value,
        ],
        'textbook-ordering' => [
            'name' => '教科書訂購',
            'url' => 'https://www2.nou.edu.tw/pd/docdetail.aspx?uid=3668&pid=3660&docid=12281',
            'group' => LinkGroup::Services->value,
        ],
        'signup' => [
            'name' => '新生網路報名系統',
            'url' => 'https://sol.nou.edu.tw/sol/signup/',
            'group' => LinkGroup::Services->value,
        ],
        'new-student-guide' => [
            'name' => '新生練功祕笈',
            'url' => 'https://www106.nou.edu.tw/~program/utf8/html5/index.html',
            'group' => LinkGroup::Services->value,
        ],
        // endregion
    ],

    // 學習指導中心：資料結構與 links 不同（含地址、電話、經緯度等實體據點資訊），故獨立存放
    'centers' => [
        'keelung-center' => [
            'name' => '基隆中心',
            'url' => 'https://www2.nou.edu.tw/keelung/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學+基隆學習指導中心/@25.1494506,121.779072,17z/data=!4m6!3m5!1s0x345d4f39caae47c1:0xe9965ff0b46b920!8m2!3d25.1494506!4d121.779072!16s%2Fg%2F11rcybw19x',
            'region' => CenterRegion::North->value,
            'address' => '202 基隆市中正區北寧路2號（海洋大學海空大樓8樓）',
            'phone' => [
                ['display' => '02-2462-9938', 'link' => '0224629938'],
            ],
            'longitude' => 121.778864,
            'latitude' => 25.1494667,
            'transport_url' => 'https://www2.nou.edu.tw/keelung/List.aspx?uid=2457&pid=2276',
        ],
        'taipei-center' => [
            'name' => '台北中心',
            'url' => 'https://www2.nou.edu.tw/taipei/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學臺北學習指導中心/@25.0877554,121.4686223,17z/data=!4m6!3m5!1s0x3442abeea709fee9:0x96b7b51a1cf3b484!8m2!3d25.0877554!4d121.4686223!16s%2Fg%2F119vl0t6p',
            'region' => CenterRegion::North->value,
            'address' => '241 新北市蘆洲區中正路172號（北院4010辦公室）',
            'phone' => [
                ['display' => '02-2282-9355 分機 3111', 'link' => '0222829355;3111'],
                ['display' => '02-2282-9355 分機 3112', 'link' => '0222829355;3112'],
            ],
            'longitude' => 121.4687421,
            'latitude' => 25.0872728,
            'transport_url' => 'https://www2.nou.edu.tw/taipei/List.aspx?uid=3764&pid=3702',
        ],
        'matsu-office' => [
            'name' => '馬祖辦公室',
            'url' => 'https://www2.nou.edu.tw/matsu/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學臺北學習指導中心/@25.0877554,121.4686223,17z/data=!4m6!3m5!1s0x3442abeea709fee9:0x96b7b51a1cf3b484!8m2!3d25.0877554!4d121.4686223!16s%2Fg%2F119vl0t6p',
            'region' => CenterRegion::OffshoreIslands->value,
            'address' => '241 新北市蘆洲區中正路172號（與台北中心共用）',
            'phone' => [
                ['display' => '02-2282-9355 分機 3114', 'link' => '0222829355;3114'],
                ['display' => '02-2282-9355 分機 3118', 'link' => '0222829355;3118'],
            ],
            'longitude' => 121.4687421,
            'latitude' => 25.0872728,
        ],
        'taoyuan-center' => [
            'name' => '桃園中心',
            'url' => 'https://www2.nou.edu.tw/taoyuan/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學桃園學習指導中心（洽辦業務請由大門警衛室換證進入）/@24.9524455,121.217419,17z/data=!3m1!4b1!4m6!3m5!1s0x3468224c7c19bd93:0xb80c4b72fab0aa18!8m2!3d24.9524455!4d121.2199939!16s%2Fg%2F11c45jy92k',
            'region' => CenterRegion::North->value,
            'address' => '320 桃園市中壢區德育路36號（中壢家商內）',
            'phone' => [
                ['display' => '03-422-6121', 'link' => '034226121'],
            ],
            'longitude' => 121.2199939,
            'latitude' => 24.9524455,
            'transport_url' => 'https://www2.nou.edu.tw/taoyuan/List.aspx?uid=3188&pid=3066',
        ],
        'hsinchu-center' => [
            'name' => '新竹中心',
            'url' => 'https://www2.nou.edu.tw/hsinchu/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University/@24.7845009,120.9970305,17z/data=!3m1!4b1!4m6!3m5!1s0x346836056d4729e5:0xf75895984d3d5ace!8m2!3d24.7845009!4d120.9970305!16s%2Fg%2F11ydgyr0y',
            'region' => CenterRegion::North->value,
            'address' => '300 新竹市大學路1001號（陽明交大光復校區綜合一館一樓）',
            'phone' => [
                ['display' => '03-572-0930 分機 1316', 'link' => '035720930;1316'],
            ],
            'longitude' => 120.9967609,
            'latitude' => 24.7844791,
            'transport_url' => 'https://www2.nou.edu.tw/hsinchu/List.aspx?uid=1235&pid=1223',
        ],
        'taichung-center' => [
            'name' => '台中中心',
            'url' => 'https://www2.nou.edu.tw/taichung/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University/@24.1218038,120.6727056,17z/data=!3m1!4b1!4m6!3m5!1s0x34693d1d21f6a6a7:0x6089940f576fb1e0!8m2!3d24.1218038!4d120.6727056!16s%2Fg%2F11_j_8r_7',
            'region' => CenterRegion::Central->value,
            'address' => '402 臺中市南區興大路145號（中興大學綜合教學大樓12樓）',
            'phone' => [
                ['display' => '04-2286-0150', 'link' => '0422860150'],
            ],
            'longitude' => 120.6724464,
            'latitude' => 24.12202,
        ],
        'changhua-office' => [
            'name' => '彰化辦公室',
            'url' => 'https://www2.nou.edu.tw/changhua/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學彰化辦公室/@23.9605805,120.5793531,17z/data=!3m1!4b1!4m6!3m5!1s0x3469372a794dbd85:0x50e5380716915b90!8m2!3d23.9605805!4d120.5793531!16s%2Fg%2F11jf9s6qgd',
            'region' => CenterRegion::Central->value,
            'address' => '510 彰化縣員林市三民東街221號（員林國小內）',
            'phone' => [
                ['display' => '04-833-0257', 'link' => '048330257'],
            ],
            'longitude' => 120.5787261,
            'latitude' => 23.9605277,
        ],
        'nantou-office' => [
            'name' => '南投辦公室',
            'url' => 'https://www2.nou.edu.tw/nantou/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學臺中學習指導中心(南投辦公室)/@23.9551621,120.6861968,17z/data=!3m1!4b1!4m6!3m5!1s0x346931799e07e2c3:0x8661aa29103ee877!8m2!3d23.9551621!4d120.6887717!16s%2Fg%2F11lg2x6t7z',
            'region' => CenterRegion::Central->value,
            'address' => '540 南投縣南投市光華路11-1號',
            'phone' => [
                ['display' => '049-239-0326', 'link' => '0492390326'],
            ],
            'longitude' => 120.6887717,
            'latitude' => 23.9551621,
        ],
        'chiayi-center' => [
            'name' => '嘉義中心',
            'url' => 'https://www2.nou.edu.tw/chiayi/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University/@23.4883903,120.4591483,17z/data=!3m1!4b1!4m6!3m5!1s0x346e95b58d9e0ac7:0x1779a7abee2e97cd!8m2!3d23.4883903!4d120.4591483!16s%2Fg%2F11xc654s2',
            'region' => CenterRegion::South->value,
            'address' => '600 嘉義市林森東路151號',
            'phone' => [
                ['display' => '05-276-4385', 'link' => '052764385'],
            ],
            'longitude' => 120.4591462,
            'latitude' => 23.4883933,
        ],
        'yunlin-office' => [
            'name' => '雲林辦公室',
            'url' => 'https://www2.nou.edu.tw/yunlin/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學雲林服務處/@23.69956,120.5401296,17z/data=!3m1!4b1!4m6!3m5!1s0x346ec9912eb0952b:0xe99eedf75204201f!8m2!3d23.69956!4d120.5401296!16s%2Fg%2F11k2wf5j1n',
            'region' => CenterRegion::Central->value,
            'address' => '640 雲林縣斗六市南揚街60號（雲林縣教師研習中心3樓）',
            'phone' => [
                ['display' => '05-536-0056', 'link' => '055360056'],
            ],
            'longitude' => 120.5401162,
            'latitude' => 23.6995846,
            'transport_url' => 'https://www2.nou.edu.tw/yunlin/docdetail.aspx?uid=2412&pid=2361&docid=10778',
        ],
        'tainan-center' => [
            'name' => '台南中心',
            'url' => 'https://www2.nou.edu.tw/tainan/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University/@23.0003204,120.214957,17z/data=!3m1!4b1!4m6!3m5!1s0x346e76f2c1dc16cb:0x200b65324b4ee2de!8m2!3d23.0003204!4d120.214957!16s%2Fg%2F11xm35gsp',
            'region' => CenterRegion::South->value,
            'address' => '701 臺南市東區大學路一號（成功大學光復校區工設二館1樓）',
            'phone' => [
                ['display' => '06-274-6666 分機 1600', 'link' => '062746666;1600'],
            ],
            'longitude' => 120.2148665,
            'latitude' => 23.0003510,
        ],
        'kaohsiung-center' => [
            'name' => '高雄中心',
            'url' => 'https://kaohsiung.nou.edu.tw',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學高雄中心/@22.6382925,120.3219739,17z/data=!3m1!4b1!4m6!3m5!1s0x346e04c21ffcf8f3:0xa79bc1502414bf3e!8m2!3d22.6382925!4d120.3219739!16s%2Fg%2F11bwqbnwgs',
            'region' => CenterRegion::South->value,
            'address' => '807 高雄市三民區九如一路797號（科工館南館）',
            'phone' => [
                ['display' => '07-380-0566', 'link' => '073800566'],
            ],
            'longitude' => 120.3219846,
            'latitude' => 22.6383618,
            'transport_url' => 'https://kaohsiung.nou.edu.tw/f2cont.aspx?id=5yBwBg+oqQY=',
        ],
        'yilan-center' => [
            'name' => '宜蘭中心',
            'url' => 'https://www2.nou.edu.tw/yilan/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University+Ilan+Center/@24.7476416,121.7456232,17z/data=!3m1!4b1!4m6!3m5!1s0x3467e4ea54b05777:0x774547ba4823a4b!8m2!3d24.7476416!4d121.7456232!16s%2Fg%2F11xy0q97n',
            'region' => CenterRegion::East->value,
            'address' => '260 宜蘭縣宜蘭市神農路一段1號（宜蘭大學經德大樓6樓）',
            'phone' => [
                ['display' => '03-933-0291', 'link' => '039330291'],
            ],
            'longitude' => 121.7455432,
            'latitude' => 24.7476550,
            'transport_url' => 'https://www2.nou.edu.tw/yilan/List.aspx?uid=2842&pid=2601',
        ],
        'hualien-center' => [
            'name' => '花蓮中心',
            'url' => 'https://www2.nou.edu.tw/hualien/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/空中大學花蓮中心/@24.0105842,121.6182389,17z/data=!3m1!4b1!4m6!3m5!1s0x34689e3b2994dce1:0x615f19ce50875706!8m2!3d24.0105842!4d121.6182389!16s%2Fg%2F11zbyl2cr',
            'region' => CenterRegion::East->value,
            'address' => '970 花蓮市華西路123號',
            'phone' => [
                ['display' => '03-822-2148', 'link' => '038222148'],
            ],
            // 24.01056457754648, 121.61822816874664
            'longitude' => 121.6182282,
            'latitude' => 24.0105646,
        ],
        'taitung-center' => [
            'name' => '台東中心',
            'url' => 'https://www2.nou.edu.tw/TAiTung/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University/@22.7553023,121.1223041,17z/data=!3m1!4b1!4m6!3m5!1s0x346fb9a654dc5103:0xf5878dd4856313fc!8m2!3d22.7553023!4d121.1223041!16s%2Fg%2F11cfcpg48',
            'region' => CenterRegion::East->value,
            'address' => '950 台東市山西路一段180號（台東專科學校後面）',
            'phone' => [
                ['display' => '08-933-6592', 'link' => '089336592'],
            ],
            'longitude' => 121.1223041,
            'latitude' => 22.7553913,
            'transport_url' => 'https://www2.nou.edu.tw/TAiTung/List.aspx?uid=2508&pid=2299',
        ],
        'penghu-center' => [
            'name' => '澎湖中心',
            'url' => 'https://www2.nou.edu.tw/penghu/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/國立空中大學澎湖學習指導中心/@23.5608153,119.5793717,17z/data=!3m1!4b1!4m6!3m5!1s0x346c5abf92ffbac7:0xe8d545efff793003!8m2!3d23.5608153!4d119.5819466!16s%2Fg%2F1tfkt1q7',
            'region' => CenterRegion::OffshoreIslands->value,
            'address' => '880 澎湖縣馬公市東文里文學路285號',
            'phone' => [
                ['display' => '06-921-4318 分機 9', 'link' => '069214318;9'],
            ],
            'longitude' => 119.5819681,
            'latitude' => 23.5609136,
            'transport_url' => 'https://www2.nou.edu.tw/penghu/docdetail.aspx?uid=2762&pid=2577&docid=10952',
        ],
        'kinmen-center' => [
            'name' => '金門中心',
            'url' => 'https://www2.nou.edu.tw/kinmen/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Aerial+University/@24.4222718,118.3119803,17z/data=!3m1!4b1!4m6!3m5!1s0x3414a211ea8beaa1:0x21e8fc777552019!8m2!3d24.4222718!4d118.3119803!16s%2Fg%2F1tvdgf43',
            'region' => CenterRegion::OffshoreIslands->value,
            'address' => '893 金門縣金城鎮西海路3段81巷2號',
            'phone' => [
                ['display' => '082-329-971', 'link' => '082329971'],
                ['display' => '082-329-972', 'link' => '082329972'],
            ],
            'longitude' => 118.3119744,
            'latitude' => 24.4222757,
        ],
        'overseas-student-services' => [
            'name' => '海外學生服務組',
            'url' => 'https://www2.nou.edu.tw/overseas/index.aspx',
            'google_maps_url' => 'https://www.google.com/maps/place/National+Open+University/@24.1218038,120.6727056,17z/data=!3m1!4b1!4m6!3m5!1s0x34693d1d21f6a6a7:0x6089940f576fb1e0!8m2!3d24.1218038!4d120.6727056!16s%2Fg%2F11_j_8r_7',
            'region' => CenterRegion::Overseas->value,
            'address' => '402 臺中市南區興大路145號（中興大學綜合教學大樓1204辦公室）',
            'phone' => [
                ['display' => '04-2286-0150 分機 1431', 'link' => '0422860150;1431'],
            ],
            'longitude' => 120.6724464,
            'latitude' => 24.12202,
            'transport_url' => 'https://www2.nou.edu.tw/overseas/docdetail.aspx?uid=4156&pid=4085&docid=13174',
        ],
    ],
];
