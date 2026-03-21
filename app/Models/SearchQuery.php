<?php 

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Ad;
use App\Models\ParserTask;

class SearchQuery extends Model
{
    protected $table = 'search_queries';
    protected $guarded = [];


    public function ads() : HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function parserTask() : HasOne 
    {
        return $this->hasOne(ParserTask::class);
    }

    public function getAdsCountAttribute() {
        return $this->ads()->count();
    }
   
}