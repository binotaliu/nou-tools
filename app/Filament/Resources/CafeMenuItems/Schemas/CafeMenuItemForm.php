<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeMenuItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CafeMenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('名稱')
                    ->required()
                    ->maxLength(32),
                TextInput::make('emoji')
                    ->label('表情符號')
                    ->helperText('顯示在座位上的杯子。')
                    ->required()
                    ->maxLength(16),
                Select::make('cup_style')
                    ->label('杯子樣式')
                    ->options(['mug' => '馬克杯', 'glass' => '玻璃杯', 'teacup' => '茶杯'])
                    ->required()
                    ->default('mug'),
                TextInput::make('price')
                    ->label('價格（橡實）')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required()
                    ->default(3),
                TextInput::make('description')
                    ->label('簡介')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('供應中')
                    ->default(true),
            ]);
    }
}
