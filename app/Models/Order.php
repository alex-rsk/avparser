<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo, HasOne};
use App\Models\{Customer, SearchQuery, AvitoCategory};

class Order extends Model
{
    protected $table = 'orders';

    protected $guarded =  [];

    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function searchQueries() : HasMany 
    {
        return $this->hasMany(SearchQuery::class);
    }

    public function category() : HasOne
    {
        return $this->hasOne(AvitoCategory::class, 'category_id');
    }
    
}
