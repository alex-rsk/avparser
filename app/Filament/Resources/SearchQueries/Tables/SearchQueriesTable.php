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
                TextColumn::make('title')->label('Название'),
                TextColumn::make('mode')->label('Что ищем')->formatStateUsing(fn($state) => match($state) {
                    'url' => 'URL запроса',
                    'text' => 'Текстовый запрос'
                }),
                //TextColumn::make('query_text')->label('Запрос'),                
                TextColumn::make('queryable')->label('Искомое')->getStateUsing(function ($record) {
                    return $record->mode == 'url' ? '<a style="color:blue" href="'.$record->category_url.'">Ссылка</a>' : '<span>'.$record->query_text.'</span>';
                })->html(),
                TextColumn::make('parserTask.status')->label('Парсер')->formatStateUsing(fn($state) => match($state){
                    'active' => 'Активен',
                    'paused' => 'На паузе',
                    'stopped' => 'Остановлен',
                    'error' => 'Ошибка'
                }),
                TextColumn::make('observed_at')->label('Обновление')->since(),
                TextColumn::make('total_pages')->label('Страниц'),
                TextColumn::make('ads_count')->label('Кол-во объявлений'),
                TextColumn::make('priority')->label('Приоритет'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
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
