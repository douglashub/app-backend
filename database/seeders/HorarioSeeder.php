<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HorarioSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('horarios')->truncate();
        
        // Insert sample data
        DB::table('horarios')->insert([
            [
                'rota_id' => 1,
                'dias_semana' => json_encode([1, 3, 5]), // Monday, Wednesday, Friday
                'hora_inicio' => '07:00',
                'hora_fim' => '07:45',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 1,
                'dias_semana' => json_encode([2, 4]), // Tuesday, Thursday
                'hora_inicio' => '07:15',
                'hora_fim' => '08:00',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 2,
                'dias_semana' => json_encode([1, 2, 3, 4, 5]), // Monday through Friday
                'hora_inicio' => '06:45',
                'hora_fim' => '07:30',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rota_id' => 3,
                'dias_semana' => json_encode([1, 2, 3, 4, 5]), // Monday through Friday
                'hora_inicio' => '06:30',
                'hora_fim' => '07:15',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}