<?php

namespace App\Filament\Resources\Settings\Tables;

use Illuminate\Support\Facades\Log;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([                
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('setting_value')
                    ->searchable()->wrap(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('editDetails')
                    ->label('Edit JSON')
                    ->modalContent(fn ($record) => view('filament.modals.json-record-edit', [
                        'record' => $record,
                        'field' => 'setting_value'
                        ])
                    )
                    ->modalHeading('Edit JSON value')
                    ->modalWidth('3xl')
                    ->visible(fn ($record) => $record->json)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->action(function ($record, $data, $action) {
                        Log::channel('daily')->debug($record->setting_value);
                        Log::channel('daily')->debug($action->data(['valueinput']));
                    })
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
