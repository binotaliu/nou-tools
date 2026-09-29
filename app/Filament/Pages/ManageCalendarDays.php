<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\IsoDateInput;
use App\Models\CalendarDay;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use NouTools\Domains\Shared\Actions\SaveCalendarDays;
use NouTools\Domains\Shared\DataTransferObjects\CalendarDayDTO;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class ManageCalendarDays extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static string|UnitEnum|null $navigationGroup = '教務管理';

    protected static ?string $navigationLabel = '日曆標註';

    protected static ?string $title = '日曆標註';

    protected static ?string $slug = 'calendar-days';

    /**
     * @var array{year: int|null, days: array<int|string, array<string, mixed>>}
     */
    public ?array $data = ['year' => null, 'days' => []];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $year = CarbonImmutable::now('Asia/Taipei')->year;

        $this->form->fill([
            'year' => $year,
            'days' => $this->daysForYear($year),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('year')
                    ->label('年份')
                    ->options($this->yearOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (int|string $state, Set $set) => $set('days', $this->daysForYear((int) $state)))
                    ->helperText('切換年份會捨棄尚未儲存的修改。儲存時會以表格內容取代該年度的所有標註。日期未列出時，週六、週日仍顯示紅色。'),
                Repeater::make('days')
                    ->label('標註')
                    ->table([
                        TableColumn::make('日期')->width('11rem'),
                        TableColumn::make('紅字')->width('5rem')->alignCenter(),
                        TableColumn::make('標註文字'),
                    ])
                    ->schema([
                        IsoDateInput::make('date')
                            ->required()
                            ->distinct()
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $year = $get('../../year');

                                if (! str_starts_with((string) $value, "{$year}-")) {
                                    $fail("日期必須在 {$year} 年內。");
                                }
                            }),
                        Toggle::make('is_red')->default(true),
                        TextInput::make('label')->maxLength(20),
                    ])
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->addActionLabel('新增標註')
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([$this->getFormActionsContentComponent()]),
        ]);
    }

    public function save(SaveCalendarDays $saveCalendarDays): void
    {
        $data = $this->form->getState();

        $days = collect($data['days'] ?? [])
            ->sortBy('date')
            ->map(fn (array $day): CalendarDayDTO => new CalendarDayDTO(
                date: CarbonImmutable::parse($day['date']),
                isRed: (bool) $day['is_red'],
                label: filled($day['label'] ?? null) ? $day['label'] : null,
            ))
            ->values()
            ->all();

        $saveCalendarDays((int) $data['year'], $days);

        Notification::make()->success()->title('已儲存日曆標註')->send();
    }

    /**
     * @return array<Action>
     */
    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('儲存')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    private function getFormActionsContentComponent(): Component
    {
        return Actions::make($this->getFormActions())->key('form-actions');
    }

    /**
     * Years that already have marks plus a window around the current year.
     *
     * @return array<int, string>
     */
    private function yearOptions(): array
    {
        $currentYear = CarbonImmutable::now('Asia/Taipei')->year;

        return collect(range($currentYear - 1, $currentYear + 2))
            ->merge(
                CalendarDay::query()
                    ->pluck('date')
                    ->map(fn (CarbonImmutable $date): int => $date->year)
            )
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function daysForYear(int $year): array
    {
        return CalendarDay::query()
            ->between("{$year}-01-01", "{$year}-12-31")
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn (CalendarDay $day): array => [
                'record-'.$day->id => [
                    'date' => $day->date->format('Y-m-d'),
                    'is_red' => $day->is_red,
                    'label' => $day->label,
                ],
            ])
            ->all();
    }
}
