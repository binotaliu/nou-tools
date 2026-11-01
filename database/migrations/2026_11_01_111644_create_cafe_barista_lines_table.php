<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cafe_barista_lines', function (Blueprint $table) {
            $table->id();
            $table->string('trigger', 32)->index();
            $table->json('conditions')->nullable();
            $table->text('text');
            $table->string('mood', 16)->default('happy');
            // Up to three {label, response, action} buttons under the line.
            $table->json('replies')->nullable();
            // The highest tier with a usable line wins; weight breaks ties.
            $table->unsignedSmallInteger('priority')->default(0);
            $table->unsignedSmallInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = Date::now();
        $order = ['label' => '點餐', 'response' => '菜單在這裡，要點菜時再跟我說即可。', 'action' => 'order'];
        $chat = ['label' => '隨便聊聊', 'response' => '嗨，要來聊聊嗎？', 'action' => 'none'];

        $line = fn (string $trigger, string $text, array $conditions = [], string $mood = 'happy', array $replies = [], int $priority = 0, int $weight = 1): array => [
            'trigger' => $trigger,
            'conditions' => $conditions === [] ? null : json_encode($conditions),
            'text' => $text,
            'mood' => $mood,
            'replies' => $replies === [] ? null : json_encode($replies),
            'priority' => $priority,
            'weight' => $weight,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $period = fn (string $period): array => ['period' => $period];
        $exam = fn (string $exam, int $min, int $max): array => ['exam' => $exam, 'min_days' => $min, 'max_days' => $max];

        DB::table('cafe_barista_lines')->insert([
            $line('greeting', '歡迎光臨！今天想喝點什麼？', [], 'happy', [$order, $chat]),
            $line('greeting', '{nickname}，今天也來啦！要喝什麼呢？', [], 'happy', [$order]),
            $line('greeting', '今天很適合來杯咖啡喔！', [], 'thinking', [$order]),
            $line('greeting', '歡迎回來，{nickname}。', [], 'happy', [$order, $chat]),
            $line('first_visit', '歡迎光臨！我是這裡的店長，松鼠。有空位都可以坐喔！', [], 'happy', [$order, ['label' => '好的', 'response' => '要點餐隨時都可以跟我說。', 'action' => 'none']], 60),
            $line('time_of_day', '早安！要來杯咖啡開機一下嗎？', $period('morning'), 'happy', [$order], 10),
            $line('time_of_day', '下午了，要來杯咖啡撐一下嗎？', $period('afternoon'), 'thinking', [$order], 10),
            $line('time_of_day', '晚安，今天讀得如何呢？', $period('evening'), 'question', [$order, $chat], 10),
            $line('time_of_day', '半夜也來讀書嗎？', $period('late_night'), 'worried', [$order], 10),
            $line('weekend', '週末正是適合讀書的時候！加油，{nickname}。', [], 'happy', [$order], 10),
            $line('weekend', '假日跟平日感覺很不一樣呢。', [], 'happy', [$chat], 10),
            $line('studied_today', '今天專注了 {study_minutes} 分鐘了，好厲害！', ['min_minutes' => 60], 'happy', [$order], 20),
            $line('studied_today', '今天讀了兩個小時了，也要記得休息喔！', ['min_minutes' => 120], 'surprised', [$order], 25),
            $line('acorns_low', '看樣子你只有 {acorns} 顆橡實了，去自習室專注一下再回來吧。', ['max' => 2], 'thinking', [], 20),
            $line('acorns_many', '你已經存了 {acorns} 顆橡實！記得，橡實只能保存 30 天，記得在還沒壞掉前把他們花掉！', ['min' => 15], 'surprised', [$order], 20),
            $line('school_event_upcoming', '{event}還有 {days} 天，準備得如何？', ['min_days' => 1, 'max_days' => 14], 'question', [], 30),
            $line('school_event_upcoming', '今天就是{event}了，加油！', ['min_days' => 0, 'max_days' => 0], 'happy', [], 35),
            $line('exam_upcoming', '期中考還有 {days} 天，準備得如何？', $exam('midterm', 8, 21), 'question', [$chat], 40),
            $line('exam_upcoming', '快期中考了，準備得如何？考試範圍都看完了嗎？', $exam('midterm', 1, 7), 'worried', [$order, $chat], 42),
            $line('exam_upcoming', '期末考還有 {days} 天，現在開始複習剛剛好。', $exam('final', 8, 21), 'thinking', [$chat], 40),
            $line('exam_upcoming', '期末考快到了！準備得如何？', $exam('final', 1, 7), 'worried', [$order, $chat], 42),
            $line('exam_day', '今天{exam}對吧？加油！', [], 'happy', [], 50),
            $line('after_exam', '考完了？辛苦啦，也要記得好好休息喔！', ['max_days_since' => 3], 'happy', [$order], 40),
            $line('small_talk', '聽說松鼠都會把橡實藏起來然後忘記在哪。我也常常忘記自己把東西放到哪裡了⋯⋯', [], 'happy', [], 5),
            $line('small_talk', '讀書的時候也要記得讓眼睛休息一下喔～', [], 'thinking', [], 5),
            $line('small_talk', '有人在便利貼上畫了很可愛的圖呢！', [], 'happy', [], 5),
            $line('small_talk', '無聊的時候可以來跟我說話喔！', [], 'happy', [], 5),
            $line('order_thanks', '好的，馬上來！', [], 'happy', [], 0),
            $line('order_thanks', '{drink}來囉，請慢用。', [], 'happy', [], 0),
            $line('offer_sticky_note', '有點餐就可以跟我買便利貼，5 顆橡實一張。', [], 'happy', [
                ['label' => '購買', 'response' => '選一張你喜歡的顏色吧。', 'action' => 'note'],
                ['label' => '下次再說', 'response' => '想買的時候再跟我說喔～', 'action' => 'none'],
            ], 10),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafe_barista_lines');
    }
};
