<?php

namespace App\Filament\Resources\ParserTasks\Pages;

use App\Filament\Resources\ParserTasks\ParserTaskResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParserTask extends ViewRecord
{
    protected static string $resource = ParserTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
