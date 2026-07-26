<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStores\Tables;

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiscountStoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge(),
                TextColumn::make('type')
                    ->label('類型'),
                TextColumn::make('category.name')
                    ->label('分類')
                    ->sortable(),
                TextColumn::make('city')
                    ->label('縣市')
                    ->toggleable(),
                TextColumn::make('reports_count')
                    ->label('回報數')
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->label('留言數')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(DiscountStoreStatus::class),
                SelectFilter::make('type')
                    ->label('類型')
                    ->options(DiscountStoreType::class),
                SelectFilter::make('category_id')
                    ->label('分類')
                    ->relationship('category', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
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
