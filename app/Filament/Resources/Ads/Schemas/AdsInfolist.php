<?php

namespace App\Filament\Resources\Ads\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Icons\Heroicon;

class AdsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')->label('Название'),
                TextEntry::make('last_visited_at')->label('Дата просмотра')->formatStateUsing(fn($q) => $q ? $q->format('Y-m-d H:i:s'): 'Не просмотрено'),
                TextEntry::make('price')->label('Цена'),
                TextEntry::make('is_promoted')->label('Продвинут')->icon(fn (bool $state): Heroicon => 
                    match ($state) { 
                        true => Heroicon::Check,
                        false => Heroicon::NoSymbol
                    }
                )->formatStateUsing(fn($state) => ''),
                TextEntry::make('clean_url')->label('URL')->formatStateUsing(
                    fn($state) => '<a href="http://avito.ru'.$state.'">http://avito.ru'.$state.'</a>')->html(),
            ]);
    }
}
