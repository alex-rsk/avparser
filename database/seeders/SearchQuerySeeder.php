<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SearchQuery;

class SearchQuerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [ 
            [
                'query_text' => 'Строительные материалы',
                'created_at' => now()
            ],
            [
                'query_text' => 'Компьютерные комплектующие',
                'created_at' => now()
            ]
        ];

        SearchQuery::insert($rows);

    }
}
