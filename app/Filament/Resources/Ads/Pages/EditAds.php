<?php

namespace App\Filament\Resources\Ads\Pages;

use App\Filament\Resources\Ads\AdsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAds extends EditRecord
{
    protected static string $resource = AdsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
