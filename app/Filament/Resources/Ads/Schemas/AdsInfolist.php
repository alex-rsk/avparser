<?php

namespace App\Filament\Resources\Ads\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;

class AdsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
            ]);
    }
}
