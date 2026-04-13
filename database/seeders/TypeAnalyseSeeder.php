<?php

namespace Database\Seeders;

use App\Models\TypeAnalyse;
use Illuminate\Database\Seeder;

class TypeAnalyseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeAnalyse::factory()->count(5)->create();
    }
}
