<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RotaParadaSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data - FIXED TABLE NAME to match migration
        DB::table('rota_parada')->truncate();
        
        // Insert sample data
        DB::table('rota_parada')->insert([
            // Rota 1
            [
                'rota_id' => 1,
                'parada_id' => 1,
                'ordem' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 1,
                'parada_id' => 2,
                'ordem' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 1,
                'parada_id' => 5,
                'ordem' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Rota 2
            [
                'rota_id' => 2,
                'parada_id' => 2,
                'ordem' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 2,
                'parada_id' => 3,
                'ordem' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 2,
                'parada_id' => 1,
                'ordem' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Rota 3
            [
                'rota_id' => 3,
                'parada_id' => 4,
                'ordem' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 3,
                'parada_id' => 5,
                'ordem' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 3,
                'parada_id' => 1,
                'ordem' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
