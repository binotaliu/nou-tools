<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('信箱')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles')
                    ->label('角色')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '無';
                        }

                        return collect($state)
                            ->map(fn (string $role) => UserRole::from($role)->label())
                            ->join(', ');
                    })
                    ->sortable(),

                TextColumn::make('email_verified_at')
                    ->label('信箱驗證時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
