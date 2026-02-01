<?php

namespace App\Filament\Resources\Ads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class AdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('url')->formatStateUsing(fn ($state) => '<a style="color:blue" href="https://avito.ru'.$state.'" target="_blank">link</a>')->html(),
                TextColumn::make('placed_at')->formatStateUsing(fn ($state) => $state?->format('Y-m-d H:i:s') ?? '-'),
                TextColumn::make('created_at'),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
