<?php 

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Ad;

class SearchQuery extends Model
{
    protected $table = 'search_queries';
    protected $guarded = [];

    public function ads() : HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function getAdsCountAttribute() {
        return $this->ads()->count();
    }
   
}