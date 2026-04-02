<?php

namespace App\Filament\Resources\SearchQueries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\View;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use App\Services\ParserPool;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use App\Models\Settings;
use Illuminate\Support\Facades\URL;

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
                TextColumn::make('observed_at')->label('Обновление')->getStateUsing(function ($record) : ?string {
                    $searchQueryId = $record->id;
                    $logFileName = storage_path('logs/' . \App\Services\ParserService::LOG_PREFIX . $searchQueryId . '.log');
                    if (file_exists($logFileName)) {
                        $lastActivityTime = filemtime($logFileName);
                        return date('Y-m-d H:i:s');
                    }
                    return null;
                })?->since(),
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
                    ->icon(function($record) {
                        if ($record->is_enabled == 0) {
                            return 'heroicon-o-play';
                        } else {
                            return 'heroicon-o-pause';
                        }
                    })
                    ->label(function($record){
                        if ($record->is_enabled == 1) {
                            return 'Остановить';
                        } else {
                            return 'Запустить';
                        }
                    })
                    ->action(function ($record): void {
                        if ($record->is_enabled == 1) {
                            $pool = new ParserPool();
                            $record->is_enabled = 0;
                            $record->save();
                        }
                        else {

                            $pool = new ParserPool();
                            $runningTasks = $pool->getActualProcessesCount();
                            $capacity = Settings::getBySlug('browser_process_count') ?? 1;
                            if ($runningTasks >= $capacity) {
                                Notification::make()
                                    ->title('Не могу запустить парсер сейчас')
                                    ->body('Лимит одновременно запущенных экземпляров парсера достигнут. Увеличьте лимит или остановите часть задач')
                                    ->warning()
                                    ->send();
                            } else {
                                $record->is_enabled = 1;
                                $record->save();
                            }
                        }
                    }),
                Action::make('report')
                    ->icon('heroicon-o-printer')
                    ->label('Отчёт')
                    ->url(function($record) {
                        $tsFrom =  strtotime('midnight');
                        $tsTo =  $tsFrom + 86400;
                        $url = route('reports.search-query.download', [
                                'search_query_id' => $record->id,
                                'date_from'       => date('Y-m-d', $tsFrom),
                                'date_to'         => date('Y-m-d', $tsTo)
                            ]);
                        return $url;
                    })
                    ->openUrlInNewTab()

                ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
