<?php

namespace App\Filament\Resources\ParserTasks;

use App\Filament\Resources\ParserTasks\Pages\CreateParserTask;
use App\Filament\Resources\ParserTasks\Pages\EditParserTask;
use App\Filament\Resources\ParserTasks\Pages\ListParserTasks;
use App\Filament\Resources\ParserTasks\Pages\ViewParserTask;
use App\Filament\Resources\ParserTasks\Schemas\ParserTaskForm;
use App\Filament\Resources\ParserTasks\Schemas\ParserTaskInfolist;
use App\Filament\Resources\ParserTasks\Tables\ParserTasksTable;
use App\Models\ParserTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParserTaskResource extends Resource
{
    protected static ?string $model = ParserTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string  $navigationLabel = 'Парсеры';

    protected static ?string $recordTitleAttribute = 'Парсеры';

    public static function form(Schema $schema): Schema
    {
        return ParserTaskForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParserTaskInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParserTasksTable::configure($table);
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
            'index' => ListParserTasks::route('/'),
            'create' => CreateParserTask::route('/create'),
            'view' => ViewParserTask::route('/{record}'),
            'edit' => EditParserTask::route('/{record}/edit'),
        ];
    }
}
