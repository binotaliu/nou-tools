<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStores\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '留言';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('content')
                    ->label('內容')
                    ->limit(80),
                IconColumn::make('is_approved')
                    ->label('已審核')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('留言時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('審核狀態')
                    ->options([
                        '1' => '已審核',
                        '0' => '未審核',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('通過')
                    ->action(fn ($record) => $record->update(['is_approved' => true]))
                    ->visible(fn ($record): bool => ! $record->is_approved)
                    ->requiresConfirmation(),
                Action::make('reject')
                    ->label('駁回')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['is_approved' => false]))
                    ->visible(fn ($record): bool => $record->is_approved)
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
