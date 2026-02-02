<?php

namespace App\Filament\Resources\ParserTasks\Pages;

use App\Filament\Resources\ParserTasks\ParserTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParserTasks extends ListRecords
{
    protected static string $resource = ParserTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
