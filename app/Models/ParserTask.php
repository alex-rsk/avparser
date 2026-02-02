<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumnate\Database\Eloquent\Relations\HasOne;
use App\Models\SearchQuery;


class ParserTask extends Model
{
    protected $table = 'parser_tasks';

    protected $guarded = [];

    public function searchQuery()
    {
        return $this->hasOne(SearchQuery::class, 'id', 'search_query_id');
    }
}
