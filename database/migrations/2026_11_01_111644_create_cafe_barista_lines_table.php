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
        $order = ['label' => '我想點餐', 'response' => '請看菜單，慢慢挑。', 'action' => 'order'];
        $chat = ['label' => '隨便聊聊', 'response' => '好呀，我正好有空。', 'action' => 'none'];

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
            $line('greeting', '{nickname}，來啦！老位子還幫你留著。', [], 'happy', [$order]),
            $line('greeting', '今天的豆子特別香，要不要來一杯？', [], 'thinking', [$order]),
            $line('greeting', '歡迎回來，{nickname}。', [], 'happy', [$order, $chat]),
            $line('first_visit', '第一次來嗎？我是這裡的店長松鼠。吧台、靠牆小桌、大長桌都可以坐，累了就點杯東西。', [], 'happy', [$order, ['label' => '好的', 'response' => '有問題隨時叫我。', 'action' => 'none']], 60),
            $line('time_of_day', '早安！先來一杯把腦袋叫醒吧。', $period('morning'), 'happy', [$order], 10),
            $line('time_of_day', '下午容易想睡，來杯咖啡撐一下？', $period('afternoon'), 'thinking', [$order], 10),
            $line('time_of_day', '晚上了，今天讀了多少？', $period('evening'), 'question', [$order, $chat], 10),
            $line('time_of_day', '這麼晚還沒睡？別忘了休息，身體比成績重要。', $period('late_night'), 'worried', [$order], 10),
            $line('weekend', '週末還來讀書，{nickname} 真的很自律。', [], 'happy', [$order], 10),
            $line('weekend', '假日的店裡特別悠閒，正好慢慢讀。', [], 'happy', [$chat], 10),
            $line('studied_today', '今天已經專注 {study_minutes} 分鐘了，不錯喔！', ['min_minutes' => 60], 'happy', [$order], 20),
            $line('studied_today', '專注超過兩小時了，休息一下，喝杯熱的吧。', ['min_minutes' => 120], 'surprised', [$order], 25),
            $line('acorns_low', '橡實只剩 {acorns} 顆了，去自習室專注一下再回來吧。', ['max' => 2], 'thinking', [], 20),
            $line('acorns_many', '你已經存了 {acorns} 顆橡實！它們三十天就過期，趁新鮮用掉吧。', ['min' => 15], 'surprised', [$order], 20),
            $line('school_event_upcoming', '{event} 還有 {days} 天，有準備好嗎？', ['min_days' => 1, 'max_days' => 14], 'question', [], 30),
            $line('school_event_upcoming', '今天就是{event}，加油！', ['min_days' => 0, 'max_days' => 0], 'happy', [], 35),
            $line('exam_upcoming', '期中考還有 {days} 天，{course}準備得如何？', $exam('midterm', 8, 21), 'question', [$chat], 40),
            $line('exam_upcoming', '快期中考了，準備得如何？{course}看完了嗎？', $exam('midterm', 1, 7), 'worried', [$order, $chat], 42),
            $line('exam_upcoming', '期末考還有 {days} 天，現在開始複習剛剛好。', $exam('final', 8, 21), 'thinking', [$chat], 40),
            $line('exam_upcoming', '期末考快到了！{course}的重點整理好了沒？', $exam('final', 1, 7), 'worried', [$order, $chat], 42),
            $line('exam_day', '今天{exam}，{course}對吧？深呼吸，你可以的！', [], 'happy', [], 50),
            $line('after_exam', '考完{course}了？辛苦啦，今天好好犒賞自己。', ['max_days_since' => 3], 'happy', [$order], 40),
            $line('small_talk', '聽說松鼠都會把橡實藏起來然後忘記在哪。我不會，我都記在帳本上。', [], 'happy', [], 5),
            $line('small_talk', '讀書累了就看看遠處，眼睛也要休息。', [], 'thinking', [], 5),
            $line('small_talk', '便利貼牆上有人畫了很可愛的圖，點完飲料可以去看看。', [], 'happy', [], 5),
            $line('small_talk', '坐吧台的客人可以直接跟我聊天喔。', [], 'happy', [], 5),
            $line('order_thanks', '好的，馬上來！{drink}小心燙。', [], 'happy', [], 0),
            $line('order_thanks', '{drink}來囉，慢慢享用。', [], 'happy', [], 0),
            $line('offer_sticky_note', '喝了{drink}的客人可以跟我買一張便利貼，貼在牆上跟大家說說話，要 5 顆橡實。', [], 'happy', [
                ['label' => '我想買', 'response' => '好，去牆那邊挑一張吧。', 'action' => 'note'],
                ['label' => '下次吧', 'response' => '隨時都可以。', 'action' => 'none'],
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
