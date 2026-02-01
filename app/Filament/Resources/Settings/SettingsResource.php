<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\CreateSettings;
use App\Filament\Resources\Settings\Pages\EditSettings;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Pages\ViewSettings;
use App\Filament\Resources\Settings\Schemas\SettingsForm;
use App\Filament\Resources\Settings\Schemas\SettingsInfolist;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use App\Models\Settings;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettingsResource extends Resource
{
    protected static ?string $model = Settings::class;    

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog;

    protected static ?string $recordTitleAttribute = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return SettingsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SettingsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configure($table);
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
            'index' => ListSettings::route('/'),
            'create' => CreateSettings::route('/create'),
            'view' => ViewSettings::route('/{record}'),
            'edit' => EditSettings::route('/{record}/edit'),
        ];
    }
}
