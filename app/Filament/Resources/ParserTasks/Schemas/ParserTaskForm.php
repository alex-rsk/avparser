<?php

namespace App\Filament\Resources\ParserTasks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ParserTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Select::make('search_query_id')
                    ->relationship('searchQuery', 'query_text')
                    ->required(),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }
}
