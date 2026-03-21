<?php

namespace App\Filament\Resources\SearchQueries\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;

class SearchQueriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('query_text')->label('Поисковый запрос'),
                TextInput::make('priority')->label('Приоритет')->numeric()->default(1)->hint('Чем выше, тем приоритетнее'),
            ]);
    }
}
