<?php 

namespace App\Services;


use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Log,DB};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\{SearchQuery, Ad, AdView, AdReview};
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportService
{
    public function baseReport(string $from, string $to, int $searchQueryId) : string
    {
        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        $headers = ['ID на Авито', 'Название', 'Цена', 'Ссылка', 'Продвинут', 'Всего просмотров', 'Сегодня просмотров', 'Средний балл обзоров', 'Список оценок', 'Список отзывов'];
        $widths = [15, 60, 20, 60, 20, 20, 20, 30, 20];
        $letters =  range('A','Z');

        for ($i= 0; $i< count($headers); $i++) {            
            $activeWorksheet->setCellValue(strtoupper($letters[$i]).'1', $headers[$i]);
            $activeWorksheet->getColumnDimension($letters[$i])->setWidth($widths[$i] ?? 15);
        }

        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEEEEEE'], // blue background
            ],
        ];
        $lastHeaderLetter =  $letters[count($headers)];
        $activeWorksheet->getStyle('A1:'.$lastHeaderLetter.'1')->applyFromArray($styleArray);
        
        $fromStr        = str_replace('-','_', $from);
        $toStr          = str_replace('-','_', $to);        
        $searchQuery    = SearchQuery::findOrFail($searchQueryId);
        $latinized      = Str::slug($searchQuery->title);
        $reportName     = storage_path('base_report_'.$latinized.'_'.$fromStr.'__'.$toStr.'.xlsx');

        $rows =  Ad::query()
            ->select([
                'ads.avito_id',
                'ads.title',
                'ads.price',
                DB::raw('CONCAT("https://avito.ru", ads.clean_url) AS url'),
                DB::raw('IF(ads.is_promoted>0, "Продвинут", "") AS promoted'),
                DB::raw('COALESCE(av.total_views, 0) AS total_views'),
                DB::raw('COALESCE(av.plus_views, 0) AS plus_views'),
                'ar.avg_reviews_rating',
                'ads.rating',
                'ar.reviews_ratings',
            ])
            ->leftJoin('ad_views as av', 'av.ad_id', '=', 'ads.id')
            ->leftJoinSub(
                DB::table('ad_reviews')
                    ->select([
                        'ad_id',
                        DB::raw('AVG(rating) AS avg_reviews_rating'),
                        DB::raw('GROUP_CONCAT(rating) AS reviews_ratings'),
                    ])
                    ->groupBy('ad_id'),
                'ar',
                'ar.ad_id',
                '=',
                'ads.id'
            )
            ->whereNotNull('ads.last_visited_at')
           // ->whereBetween('ads.created_at', [ $from, $to])
            ->get()->toArray();

        $writer = new Xlsx($spreadsheet);

        foreach ($rows as $rowIndex => $row) {
            $colIndex = 0;
            foreach ($row as $colName => $fieldValue) {            
                Log::channel('reports')->debug('Col:'.$colIndex);
                $excelRowIndex = $rowIndex + 2;
                $cellCoord = strtoupper($letters[$colIndex]).$excelRowIndex;                
                $activeWorksheet->setCellValue($cellCoord, $fieldValue);
                if ($colName == 'url') {
                    $activeWorksheet->getCell($cellCoord)->getHyperlink()->setUrl($fieldValue);
                }
                $colIndex++;
            }
        }

        $writer->save($reportName);
        return $reportName;
    }
}