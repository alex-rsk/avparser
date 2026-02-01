<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::statement('TRUNCATE TABLE settings;');

        $settings = [
            [
                'slug' => 'browser_process_count',
                'title' => 'Количество одновременно запущенных браузерных парсеров ',
                'setting_value' => 3,
                'json' => false 
            ],            
           
        ];

        foreach ($settings as $setting) {
            if (!Settings::where('slug', $setting['slug'])->exists()) {
                Settings::create($setting);
            }
        }

    }
}
