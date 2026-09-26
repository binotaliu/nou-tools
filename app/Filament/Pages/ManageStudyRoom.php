<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Settings\StudyRoomSettings;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManageStudyRoom extends SettingsPage
{
    protected static string $settings = StudyRoomSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = '自習室';

    protected static ?string $title = '自習室設定';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->isAdmin() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('isOpen')
                    ->label('開放自習室')
                    ->helperText('關閉後，學生將無法進入自習室、佔位或計時，僅能看到目前的公告。'),
                MarkdownEditor::make('announcement')
                    ->label('公告板內容')
                    ->helperText('以 Markdown 撰寫，將顯示在自習室頁面的公告板中。圖片請寫替代文字：![替代文字](圖片網址)；只有純裝飾的圖片才留空。')
                    ->columnSpanFull(),
                TagsInput::make('forbiddenNicknames')
                    ->label('暱稱黑名單')
                    ->helperText('比對時會忽略大小寫與空白，例如加入「壞字」也會擋下「壞 字」。')
                    ->columnSpanFull(),
            ]);
    }
}
