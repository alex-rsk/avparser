<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Text;
use App\Models\AvitoCategory;

class OrdersForm
{
    public static function configure(Schema $schema): Schema
    {
        $tree = AvitoCategory::getSubTrees();

        return $schema
            ->components([
                View::make('filament.pages.categories')->viewData(['tree' => $tree])->columnSpanFull()
            ]);
    }
}
