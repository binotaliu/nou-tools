<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeMenuItems\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CafeMenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('emoji')->label(''),
                TextColumn::make('name')->label('名稱')->searchable(),
                TextColumn::make('price')->label('價格（橡實）')->sortable(),
                IconColumn::make('is_active')->label('供應中')->boolean(),
                TextColumn::make('sort')->label('排序')->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
