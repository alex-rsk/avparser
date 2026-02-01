<?php

namespace App\Filament\Resources\SearchQueries\RelationManagers;

use App\Filament\Resources\Ads\AdsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AdsRelationManager extends RelationManager
{
    protected static string $relationship = 'ads';

    protected static ?string $relatedResource = AdsResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
