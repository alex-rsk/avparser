<?php

namespace App\Filament\Resources\Ads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;

class AdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('url')->formatStateUsing(fn ($state) => '<a style="color:blue" href="https://avito.ru'.$state.'" target="_blank">link</a>')->html(),
                TextColumn::make('last_visited_at')->label('Посещено')->sortable(),
                TextColumn::make('created_at')->label('Добавлен'),

            ])
            ->filters(
                [
                    Filter::make('is_promoted')
                        ->label('Только продвинутые')
                        ->toggle()
                        ->query(fn ($query) => $query->where('is_promoted', 1)),
                    Filter::make('last_visited_at')
                        ->label('Только просмотренные')
                        ->toggle()
                        ->query(fn ($query) => $query->whereNotNull('last_visited_at')),
                ], layout: FiltersLayout::AboveContent
            )
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make(),
                //EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
