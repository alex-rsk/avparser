<?php

namespace App\Models;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illumiinate\Database\Eloquent\Relations\HasMany;
use Illumiinate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AdView;
use App\Models\AdReview;
use App\Models\SearchQuery;

class Ad extends Model
{
    protected $table = 'ads';
    protected $guarded = [];

    public static function prepareInsertData(array $rawData, int $searchQueryId) {
        $prepared = [];
        foreach ($rawData as $item) {

            $cleanUrl = preg_replace('~\?.*$~', '', $item['link']);
            $avitoId = (fn($val) =>  preg_match('~(\d+)\?.*$~', $item['link'], $matches) ? $matches[1] : null)($item['link']);
            $price = !empty($price) ? floatval(preg_replace('~[^\d]~ ','', $price)) : null;
            $row =[
                'search_query_id' => $searchQueryId,
                'url'             => $item['link'],
                'clean_url'       => $cleanUrl,
                'title'           => $item['text'],

                'is_promoted'     => $item['promoted'] ?? 0,
                'avito_id'        => $avitoId,
                'created_at'      => date('Y-m-d H:i:s')
            ];
            if (!empty($price) && is_numeric($price)) {
                $row['price'] = $item['price'];
            }

            $prepared[] = $row;
        }

        $avitoIds = array_column($prepared, 'avito_id');
        $existingIds = self::query()->select('avito_id')->whereIn('avito_id', $avitoIds)
            ->get()->pluck('avito_id');

        $prepared = array_filter($prepared, function ($item) use ($existingIds) {
            return !in_array($item['avito_id'], $existingIds->toArray());
        });

        Log::channel('daily')->debug('New ads: '.count($prepared));

        return $prepared;
    }

    public function views()
    {
        return $this->hasMany(AdView::class);
    }

    public function reviews()
    {
        return $this->hasMany(AdReview::class);
    }

    public function searchQuery()
    {
        return $this->belongsTo(SearchQuery::class, 'search_query_id', 'id');
    }
}
