<?php

namespace App\Filament\Resources\SearchQueries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class SearchQueriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('query_text')->label('Запрос'),
                TextColumn::make('priority')->label('Приоритет'),
                TextColumn::make('parserTask.status')->label('Статус парсера')->formatStateUsing(fn($state) => match($state){
                    'active' => 'Активен',
                    'paused' => 'На паузе',
                    'stopped' => 'Остановлен',
                    'error' => 'Ошибка'
                }),
                TextColumn::make('observed_at')->label('Последнее обновление')->since(),
                TextColumn::make('total_pages')->label('Количество страниц'),
                TextColumn::make('ads_count')->label('Количество объявлений'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
                Action::make('run')
                ->icon('heroicon-o-play')
                ->label('Запустить')
                ->action(function ($record): void {        
                    $record->update(['status' => 'running']);
                })                    
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
