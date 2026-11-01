<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeBaristaLines\Schemas;

use App\Enums\BaristaTrigger;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CafeBaristaLineForm
{
    public static function configure(Schema $schema): Schema
    {
        $is = fn (BaristaTrigger ...$triggers): \Closure => fn (Get $get): bool => in_array(
            $get('trigger'),
            array_map(fn (BaristaTrigger $trigger): string => $trigger->value, $triggers),
            true,
        );

        return $schema
            ->components([
                Section::make('台詞')
                    ->schema([
                        Select::make('trigger')
                            ->label('什麼時候說')
                            ->options(collect(BaristaTrigger::cases())->mapWithKeys(fn (BaristaTrigger $trigger): array => [$trigger->value => $trigger->label()]))
                            ->required()
                            ->live(),
                        Textarea::make('text')
                            ->label('內容')
                            ->helperText('可用 {nickname} 暱稱、{course} 課程、{exam} 期中考／期末考、{days} 還有幾天、{event} 行事曆事件、{acorns} 橡實數、{study_minutes} 今天專注分鐘、{drink} 剛點的飲品。用到的資料這位客人沒有時，這句就不會出現。')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Select::make('mood')
                            ->label('店長表情')
                            ->options(['happy' => '開心', 'thinking' => '思考', 'question' => '提問', 'surprised' => '驚訝', 'worried' => '擔心'])
                            ->required()
                            ->default('happy'),
                        TextInput::make('priority')
                            ->label('優先順序')
                            ->helperText('數字越大越優先；同一級才會隨機挑。考試相關建議 40 以上。')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('weight')
                            ->label('權重')
                            ->helperText('同一級中，數字越大越常出現。')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('啟用')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('條件')
                    ->description('依「什麼時候說」顯示對應的條件，沒填的欄位不設限。')
                    ->schema([
                        Select::make('conditions.period')
                            ->label('時段')
                            ->options(['morning' => '早上（5–12 點）', 'afternoon' => '下午（12–18 點）', 'evening' => '晚上（18–23 點）', 'late_night' => '深夜（23–5 點）'])
                            ->required($is(BaristaTrigger::TimeOfDay))
                            ->visible($is(BaristaTrigger::TimeOfDay)),
                        Select::make('conditions.exam')
                            ->label('哪一種考試')
                            ->options(['any' => '都可以', 'midterm' => '期中考', 'final' => '期末考'])
                            ->default('any')
                            ->visible($is(BaristaTrigger::ExamUpcoming)),
                        TextInput::make('conditions.min_days')
                            ->label('最少還有幾天')
                            ->numeric()
                            ->minValue(0)
                            ->visible($is(BaristaTrigger::ExamUpcoming, BaristaTrigger::SchoolEventUpcoming)),
                        TextInput::make('conditions.max_days')
                            ->label('最多還有幾天')
                            ->numeric()
                            ->minValue(0)
                            ->visible($is(BaristaTrigger::ExamUpcoming, BaristaTrigger::SchoolEventUpcoming)),
                        TextInput::make('conditions.max_days_since')
                            ->label('考完幾天內')
                            ->numeric()
                            ->minValue(1)
                            ->default(3)
                            ->visible($is(BaristaTrigger::AfterExam)),
                        TextInput::make('conditions.min_minutes')
                            ->label('今天至少專注幾分鐘')
                            ->numeric()
                            ->minValue(1)
                            ->default(60)
                            ->visible($is(BaristaTrigger::StudiedToday)),
                        TextInput::make('conditions.max')
                            ->label('橡實不超過')
                            ->numeric()
                            ->default(2)
                            ->visible($is(BaristaTrigger::AcornsLow)),
                        TextInput::make('conditions.min')
                            ->label('橡實至少')
                            ->numeric()
                            ->default(15)
                            ->visible($is(BaristaTrigger::AcornsMany)),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('客人的回應按鈕')
                    ->description('最多三個。「點餐」會打開菜單，「便利貼」會帶客人到便利貼牆。')
                    ->schema([
                        Repeater::make('replies')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('label')->label('按鈕文字')->required()->maxLength(20),
                                Textarea::make('response')->label('店長的回應')->required()->rows(2)->maxLength(300),
                                Select::make('action')
                                    ->label('接下來')
                                    ->options(['none' => '沒事', 'order' => '打開菜單', 'note' => '去便利貼牆'])
                                    ->default('none')
                                    ->required(),
                            ])
                            ->maxItems(3)
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('新增按鈕'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
