<?php

namespace App\Filament\Resources\Ads;

use App\Filament\Resources\Ads\Pages\CreateAds;
use App\Filament\Resources\Ads\Pages\EditAds;
use App\Filament\Resources\Ads\Pages\ListAds;
use App\Filament\Resources\Ads\Pages\ViewAds;
use App\Filament\Resources\Ads\Schemas\AdsForm;
use App\Filament\Resources\Ads\Schemas\AdsInfolist;
use App\Filament\Resources\Ads\Tables\AdsTable;
use App\Models\Ad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdsResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Объявления';
    protected static ?string $modelLabel = 'Объявление';
    protected static ?string $pluralModelLabel = 'Объявления';

    public static function form(Schema $schema): Schema
    {
        return AdsForm::configure($schema);
    }


    public static function infolist(Schema $schema): Schema
    {
        return AdsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAds::route('/'),
            //'create' => CreateAds::route('/create'),
            //'view' => ViewAds::route('/{record}'),
            //'edit' => EditAds::route('/{record}/edit'),
        ];
    }
}
