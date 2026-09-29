<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\IsoDateInput;
use App\Models\SchoolCalendarEvent;
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
use Illuminate\Support\Str;
use NouTools\Domains\Shared\Actions\SaveSchoolCalendar;
use NouTools\Domains\Shared\DataTransferObjects\SchoolCalendarEventDTO;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class ManageSchoolCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = '教務管理';

    protected static ?string $navigationLabel = '學校行事曆';

    protected static ?string $title = '學校行事曆';

    protected static ?string $slug = 'school-calendar';

    /**
     * @var array{term: string|null, events: array<int, array<string, mixed>>}
     */
    public ?array $data = ['term' => null, 'events' => []];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'term' => (string) config('app.current_semester'),
            'events' => $this->eventsForTerm((string) config('app.current_semester')),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('term')
                    ->label('學期')
                    ->options($this->termOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (string $state, Set $set) => $set('events', $this->eventsForTerm($state)))
                    ->helperText('切換學期會捨棄尚未儲存的修改。儲存時會以表格內容取代該學期的所有事件。'),
                Repeater::make('events')
                    ->label('事件')
                    ->table([
                        TableColumn::make('開始日')->width('11rem'),
                        TableColumn::make('結束日')->width('11rem'),
                        TableColumn::make('名稱'),
                        TableColumn::make('倒數')->width('5rem')->alignCenter(),
                        TableColumn::make('重要')->width('5rem')->alignCenter(),
                    ])
                    ->schema([
                        IsoDateInput::make('start_date')->required(),
                        IsoDateInput::make('end_date')
                            ->required()
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if (filled($get('start_date')) && (string) $value < $get('start_date')) {
                                    $fail('結束日不可早於開始日。');
                                }
                            }),
                        TextInput::make('name')->required()->maxLength(255),
                        Toggle::make('is_countdown')->default(false),
                        Toggle::make('is_important')->default(true),
                    ])
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->addActionLabel('新增事件')
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

    public function save(SaveSchoolCalendar $saveSchoolCalendar): void
    {
        $data = $this->form->getState();

        $events = collect($data['events'] ?? [])
            ->sortBy(['start_date', 'end_date'])
            ->map(fn (array $event): SchoolCalendarEventDTO => new SchoolCalendarEventDTO(
                startDate: $this->date($event['start_date']),
                endDate: $this->date($event['end_date']),
                name: $event['name'],
                isCountdown: (bool) $event['is_countdown'],
                isImportant: (bool) $event['is_important'],
            ))
            ->values()
            ->all();

        $saveSchoolCalendar($data['term'], $events);

        Notification::make()->success()->title('已儲存行事曆')->send();
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
     * Existing terms plus a window of A/B/C terms around the current one, so
     * an admin can start a new semester without typing a code.
     *
     * @return array<string, string>
     */
    private function termOptions(): array
    {
        $currentYear = (int) substr((string) config('app.current_semester'), 0, 4);

        $codes = collect(range($currentYear - 1, $currentYear + 1))
            ->flatMap(fn (int $year): array => ["{$year}A", "{$year}B", "{$year}C"])
            ->merge(SchoolCalendarEvent::query()->distinct()->pluck('term'))
            ->unique()
            ->sortDesc()
            ->values();

        return $codes
            ->mapWithKeys(fn (string $code): array => [$code => Str::toSemesterDisplay($code)." ({$code})"])
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function eventsForTerm(string $term): array
    {
        return SchoolCalendarEvent::query()
            ->forTerm($term)
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->get()
            ->mapWithKeys(fn (SchoolCalendarEvent $event): array => [
                'record-'.$event->id => [
                    'start_date' => $event->start_date->format('Y-m-d'),
                    'end_date' => $event->end_date->format('Y-m-d'),
                    'name' => $event->name,
                    'is_countdown' => $event->is_countdown,
                    'is_important' => $event->is_important,
                ],
            ])
            ->all();
    }

    private function date(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value);
    }
}
