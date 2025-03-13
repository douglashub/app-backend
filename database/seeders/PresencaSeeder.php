<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresencaSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('presencas')->truncate();
        
        // Insert sample data
        DB::table('presencas')->insert([
            // Presences for Trip 1 (today)
            [
                'viagem_id' => 1,
                'aluno_id' => 1,
                'hora_registro' => '07:10',
                'presente' => true,
                'observacoes' => 'Aluno embarcou no ponto normal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'viagem_id' => 1,
                'aluno_id' => 2,
                'hora_registro' => '07:15',
                'presente' => true,
                'observacoes' => 'Aluno embarcou no ponto normal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'viagem_id' => 1,
                'aluno_id' => 3,
                'hora_registro' => '07:20',
                'presente' => false,
                'observacoes' => 'Aluno ausente - Doente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Presences for Trip 2 (today)
            [
                'viagem_id' => 2,
                'aluno_id' => 4,
                'hora_registro' => '06:55',
                'presente' => true,
                'observacoes' => 'Aluno embarcou no ponto normal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'viagem_id' => 2,
                'aluno_id' => 5,
                'hora_registro' => '07:00',
                'presente' => true,
                'observacoes' => 'Aluno embarcou no ponto normal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
