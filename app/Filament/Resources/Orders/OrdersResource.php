<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrders;
use App\Filament\Resources\Orders\Pages\EditOrders;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrders;
use App\Filament\Resources\Orders\Schemas\OrdersForm;
use App\Filament\Resources\Orders\Schemas\OrdersInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Заказ';
    protected static ?string $modelLabel = 'Заказ';
    protected static ?string $pluralModelLabel = 'Заказы';    

    public static function form(Schema $schema): Schema
    {
        return OrdersForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrdersInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrders::route('/create'),
            'view' => ViewOrders::route('/{record}'),
            'edit' => EditOrders::route('/{record}/edit'),
        ];
    }
}
