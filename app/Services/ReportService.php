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

        $searchQuery = SearchQuery::find($searchQueryId);

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->mergeCells('A1:F1');
        $activeWorksheet->setCellValue('A1', $searchQuery->query_text);
        $activeWorksheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20
            ],
        ]);

        $headers = ['ID на Авито',
         'Название',
         'Цена',
         'Ссылка',
         'Продвинут',
         'Всего просмотров',
         'Сегодня просмотров',
         // 'Рейтинг',
         'Средний балл обзоров',
         'Список оценок',
         'Комментарии'];
        $widths = [15, 60, 20, 60, 20, 20, 20, 30, 20];
        $letters =  range('A','Z');

        for ($i= 0; $i< count($headers); $i++) {
            $activeWorksheet->setCellValue(strtoupper($letters[$i]).'2', $headers[$i]);
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
        $activeWorksheet->getStyle('A2:'.$lastHeaderLetter.'2')->applyFromArray($styleArray);

        $fromStr        = str_replace('-','_', $from);
        $toStr          = str_replace('-','_', $to);
        $searchQuery    = SearchQuery::findOrFail($searchQueryId);
        $latinized      = Str::slug($searchQuery->title);
        $reportName     = storage_path('base_report_'.$latinized.'_'.$fromStr.'__'.$toStr.'.xlsx');

        $query =  Ad::query()
            ->select([
                'ads.avito_id',
                'ads.title',
                'ads.price',
                DB::raw('CONCAT("https://avito.ru", ads.clean_url) AS url'),
                DB::raw('IF(ads.is_promoted>0, "Продвинут", "") AS promoted'),
                DB::raw('COALESCE(av.total_views, 0) AS total_views'),
                DB::raw('COALESCE(av.plus_views, 0) AS plus_views'),
                //'ads.rating',
                DB::raw('COALESCE(ar.avg_reviews_rating, "") AS avg_reviews_rating'),
                DB::raw('COALESCE(ar.reviews_ratings, "") AS review_ratings'),
                DB::raw('COALESCE(ar.comments, "" ) AS comments')
            ])
            ->leftJoin('ad_views as av', 'av.ad_id', '=', 'ads.id')
            ->leftJoinSub(
                DB::table('ad_reviews')
                    ->select([
                        'ad_id',
                        DB::raw('AVG(rating) AS avg_reviews_rating'),
                        DB::raw('GROUP_CONCAT(rating) AS reviews_ratings'),
                        DB::raw('GROUP_CONCAT(comment SEPARATOR "|") AS comments'),
                    ])
                    ->groupBy('ad_id'),
                'ar',
                'ar.ad_id',
                '=',
                'ads.id'
            )
            ->where('search_query_id', $searchQueryId)
            ->whereNotNull('ads.last_visited_at');
        //    ->whereBetween('ads.created_at', [ $from, $to]);

        $sql = $query->toSql();
        Log::channel('reports')->debug('SQL:'.$sql);

        $rows = $query->get()->toArray();

        $writer = new Xlsx($spreadsheet);

        foreach ($rows as $rowIndex => $row) {
            $colIndex = 0;
            foreach ($row as $colName => $fieldValue) {
                Log::channel('reports')->debug('Col:'.$colIndex);
                $excelRowIndex = $rowIndex + 3;
                $cellCoord = strtoupper($letters[$colIndex]).$excelRowIndex;
                $activeWorksheet->setCellValue($cellCoord, $fieldValue);
                if ($colName == 'url') {
                    $activeWorksheet->getCell($cellCoord)->getHyperlink()->setUrl($fieldValue);
                    $activeWorksheet->getStyle($cellCoord)->getAlignment()->setWrapText(true);
                }
                $colIndex++;
            }
        }

        $writer->save($reportName);
        return $reportName;
    }
}
