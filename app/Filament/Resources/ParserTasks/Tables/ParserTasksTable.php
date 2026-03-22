<?php

namespace App\Filament\Resources\ParserTasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParserTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')
                    ->searchable(),
                TextColumn::make('searchQuery.query_text')
                    ->label('Поисковый запрос')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('priority')->label('Приоритет')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')->label('Статус')
                    ->sortable(),
                    TextColumn::make('stage')->label('Стадия')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
