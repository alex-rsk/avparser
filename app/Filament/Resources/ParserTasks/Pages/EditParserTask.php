<?php

namespace App\Filament\Resources\ParserTasks\Pages;

use App\Filament\Resources\ParserTasks\ParserTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParserTask extends EditRecord
{
    protected static string $resource = ParserTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
