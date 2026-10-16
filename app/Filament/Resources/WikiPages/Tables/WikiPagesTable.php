<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages\Tables;

use App\Models\WikiPage;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WikiPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currentRevision.title')
                    ->label('標題')
                    ->placeholder('—'),
                TextColumn::make('slug')
                    ->label('網址代稱')
                    ->searchable(),
                IconColumn::make('is_protected')
                    ->label('受保護')
                    ->boolean(),
                TextColumn::make('currentRevision.created_at')
                    ->label('最後更新')
                    ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei')
                    ->placeholder('—'),
                TextColumn::make('revisions_count')
                    ->label('版本數')
                    ->counts('revisions'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('is_protected')->label('受保護'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('檢視')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (WikiPage $record): string => route('wiki.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (WikiPage $record): bool => $record->current_revision_id !== null && ! $record->trashed()),
                EditAction::make(),
            ]);
    }
}
