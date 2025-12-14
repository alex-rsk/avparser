<?php 

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illumiinate\Database\Eloquent\Relations\HasMany;
use Illumiinate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AdView;
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
            $prepared[] =[
                'search_query_id' => $searchQueryId,
                'url'             => $item['link'],
                'clean_url'       => $cleanUrl,
                'title'           => $item['text'],
                'avito_id'        => $avitoId,
                'created_at'      => date('Y-m-d H:i:s')
            ];
        }
        return $prepared;
    }

    public function views() 
    {
        return $this->hasMany(AdView::class);
    }

    public function searchQuery() 
    {
        return $this->belongsTo(SearchQuery::class, 'search_query_id', 'id');
    }
}