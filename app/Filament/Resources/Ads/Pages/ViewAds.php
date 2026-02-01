<?php

namespace App\Filament\Resources\Ads\Pages;

use App\Filament\Resources\Ads\AdsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAds extends ViewRecord
{
    protected static string $resource = AdsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
