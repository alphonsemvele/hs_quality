<?php

namespace Database\Seeders;

use App\Models\TypeExamenImagerie;
use Illuminate\Database\Seeder;

class TypeExamenImagerieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeExamenImagerie::factory()->count(5)->create();
    }
}
