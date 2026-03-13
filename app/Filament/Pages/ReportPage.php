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
    protected static  ?string  $navigationLabel = 'Reports';
    protected static ?string $title = 'Search Query Report';
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
                    ->label('Search Query')
                    ->options(fn () => SearchQuery::orderBy('query_text')->pluck('query_text', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('— Select a query —')
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('date_from')
                    ->label('From')
                    ->required()
                    ->native(false)                            
                    ->displayFormat('d/m/Y'),

                DatePicker::make('date_to')
                    ->label('To')
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
