<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany,BelongsTo};
use App\Models\{Customer, SearchQuery};

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
    
}
