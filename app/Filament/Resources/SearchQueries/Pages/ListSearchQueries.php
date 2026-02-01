<?php

namespace App\Filament\Resources\SearchQueries\Pages;

use App\Filament\Resources\SearchQueries\SearchQueriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSearchQueries extends ListRecords
{
    protected static string $resource = SearchQueriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
