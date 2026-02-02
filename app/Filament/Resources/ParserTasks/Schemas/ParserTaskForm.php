<?php

namespace App\Filament\Resources\ParserTasks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ParserTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('search_query_id')
                    ->required()
                    ->numeric(),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('process_pid')
                    ->numeric(),
            ]);
    }
}
