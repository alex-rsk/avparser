<?php

namespace App\Filament\Pages;

use \BackedEnum;
use App\Exports\SearchQueryExport;
use App\Models\SearchQuery;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\FormsComponent;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;

class ReportPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static  ?string  $navigationLabel = 'Отчёты';
    protected static ?string $title = 'Отчёты';
    protected static ?int $navigationSort = 99;
    protected string $view = 'filament.pages.report-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('search_query_id')
                    ->label('Поисковый запрос')
                    ->options(fn () => SearchQuery::orderBy('id')->pluck('title', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Выберите поисковый запрос')
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('date_from')
                    ->label('Дата от')
                    ->required()
                    ->native(false)                            
                    ->displayFormat('d/m/Y'),

                DatePicker::make('date_to')
                    ->label('до')
                    ->required()
                    ->native(false)
                    ->maxDate(now())
                    ->displayFormat('d/m/Y'),
            ]                
            )
            ->statePath('data');
    }

   
    public function generateReport(): void
    {
        $this->form->validate();

        $data = $this->form->getState();

        $url = URL::temporarySignedRoute(
            'reports.search-query.download',
            now()->addMinutes(5),
            [
                'search_query_id' => $data['search_query_id'],
                'date_from'       => $data['date_from'],
                'date_to'         => $data['date_to'],
            ]
        );

        // Open the download in a new tab without navigating away
        $this->js("window.open('{$url}', '_blank')");

        Notification::make()
            ->title('Report is being generated')
            ->body('Your Excel file will download shortly.')
            ->success()
            ->send();
    }
}
