<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::truncate();
        Customer::create([
            'name' => 'Тест Тестович',
            'email' => config('app.default_customer_email'),
            'status' => 'active'
        ]);
    }
}
