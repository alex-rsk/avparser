<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SettingsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('uuid')
                    ->label('UUID'),
                TextEntry::make('slug'),
                TextEntry::make('title'),
                TextEntry::make('setting_value')
                    ->columnSpanFull(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
