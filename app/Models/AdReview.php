<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illumiinate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Ad;

class AdReview extends Model
{
    protected $table = 'ad_reviews';
    protected $guarded = [];

    public function Ad() : BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
