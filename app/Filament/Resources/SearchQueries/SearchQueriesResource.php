<?php

namespace App\Filament\Resources\SearchQueries;

use App\Filament\Resources\SearchQueries\Pages\CreateSearchQueries;
use App\Filament\Resources\SearchQueries\Pages\EditSearchQueries;
use App\Filament\Resources\SearchQueries\Pages\ListSearchQueries;
use App\Filament\Resources\SearchQueries\Schemas\SearchQueriesForm;
use App\Filament\Resources\SearchQueries\Tables\SearchQueriesTable;
use App\Models\SearchQuery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\SearchQueries\RelationManagers\AdsRelationManager;


class SearchQueriesResource extends Resource
{
    protected static ?string $model = SearchQuery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Search queries';

    protected static ?string  $navigationLabel = 'Поисковые запросы';

    public static function form(Schema $schema): Schema
    {
        return SearchQueriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SearchQueriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSearchQueries::route('/'),
            'create' => CreateSearchQueries::route('/create'),
            'edit' => EditSearchQueries::route('/{record}/edit'),
        ];
    }
}
