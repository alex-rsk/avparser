<?php

namespace App\Filament\Resources\SearchQueries\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;

class SearchQueriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Название'),
                ToggleButtons::make('mode')
                    ->live()
                    ->label('Режим')
                    ->options(['text' => 'Текстовый запрос', 'url' => 'URL с фильтрами'])
                    ->inline()
                    ->default('url'),
                Textarea::make('query_text')->label('Поисковый запрос')
                    ->rows(3)
                    ->visible(fn ($get): bool => $get('mode') === 'text')
                    ->hint('Программно вводится в строку поиска avito'),
                Textarea::make('category_url')->label('URL с фильтрами')
                    ->rows(5)
                    ->hint('Собираются объявления по этому URL')
                    ->visible(fn ($get): bool => $get('mode') === 'url'),
                TimePicker::make('launch_time')
                    ->format('H:i')
                    ->native(false)
                    ->seconds(false)
                    ->timezone('Europe/Moscow')
                    ->label('Время запуска'),
                TextInput::make('priority')->label('Приоритет')->numeric()->default(1)->hint('Чем выше, тем приоритетнее'),
            ]);
    }
}
