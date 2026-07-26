<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStores\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '回報紀錄';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_valid')
                    ->label('有效')
                    ->boolean(),
                TextColumn::make('comment')
                    ->label('備註')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('回報時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_valid')
                    ->label('類型')
                    ->options([
                        '1' => '有效',
                        '0' => '無效',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
