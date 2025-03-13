<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnibusSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('onibus')->truncate();
        
        // Insert sample data
        DB::table('onibus')->insert([
            [
                'placa' => 'ABC1D234',
                'modelo' => 'Mercedes Benz O500U',
                'capacidade' => 40,
                'ano_fabricacao' => 2020,
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'placa' => 'DEF2G567',
                'modelo' => 'Volkswagen 17.230 OD',
                'capacidade' => 35,
                'ano_fabricacao' => 2019,
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'placa' => 'GHI3J890',
                'modelo' => 'Mercedes Benz OF-1519',
                'capacidade' => 30,
                'ano_fabricacao' => 2021,
                'status' => 'Manutenção',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
