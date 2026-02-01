<?php

namespace App\Filament\Resources\SearchQueries\Pages;

use App\Filament\Resources\SearchQueries\SearchQueriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSearchQueries extends EditRecord
{
    protected static string $resource = SearchQueriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
