<?php 

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasOne, HasMany, BelongsTo};
use App\Models\{Ad, ParserTask, Order, Customer};

class SearchQuery extends Model
{
    protected $table = 'search_queries';
    protected $guarded = [];

    public static function boot() {
        parent::boot();

        static::created(function(SearchQuery $model) {

            $customer = Customer::firstOrCreate(
                ['id' => 1], 
                ['name' => 'Тестовый заказчик', 'email' => config('app.default_customer_email')]
            );

            $order = Order::create([
                'customer_id'    => $customer->id,
                'order_type'     => 1,
                'title'          => "Заказ ".$model->query_text
            ]);

            $model->order_id = $order->id;
            $model->save();
        });
    }



    public function ads() : HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function parserTask() : HasOne 
    {
        return $this->hasOne(ParserTask::class);
    }

    public function order() : BelongsTo 
    {
        return $this->belongsTo(Order::class);
    }


    public function getAdsCountAttribute() {
        return $this->ads()->count();
    }
   
}