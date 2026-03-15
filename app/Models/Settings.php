<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    const CREATED_AT = null;

    protected $table = 'settings';

    protected $guarded = [];


    /*
     * Получить настройку по slug-у
     * 
     * @param string $slug
     * 
     * @return mixed
     * Throws Exception
     */
    public static function getBySlug(string $slug) : mixed
    {
        $setting = self::query()->where('slug', $slug)->first();

        if (is_null($setting)) {
            throw new \Exception("Setting $slug not found");
        }

        return !!$setting->json ? json_decode($setting->setting_value, true) : $setting->setting_value;

    }

}
