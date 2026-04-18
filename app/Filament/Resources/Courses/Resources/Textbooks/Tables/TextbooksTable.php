<?php

namespace App\Filament\Resources\Courses\Resources\Textbooks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TextbooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('term')
                    ->label('學期')
                    ->sortable(),
                TextColumn::make('book_title')
                    ->label('書名')
                    ->searchable(),
                TextColumn::make('edition')
                    ->label('版本'),
                TextColumn::make('price_info')
                    ->label('價格或補充資訊')
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
