<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumnate\Database\Eloquent\Relations\HasOne;
use App\Models\SearchQuery;
use App\Services\ParserPool;


class ParserTask extends Model
{
    protected $table = 'parser_tasks';

    protected $guarded = [];

    public static function boot(){
        parent::boot();

        static::deleted(function(ParserTask $model) 
        {
            $searchQuery = $model->searchQuery;
            $searchQuery->is_enabled = false;
            $searchQuery->save();

            $pool = new ParserPool();
            $pool->removeBrowserInstance($model);
            return true;
        });
    }

    public function searchQuery()
    {
        return $this->hasOne(SearchQuery::class, 'id', 'search_query_id');
    }
}
