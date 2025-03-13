<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RotaSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('rotas')->truncate();
        
        // Insert sample data
        DB::table('rotas')->insert([
            [
                'nome' => 'Rota Centro-Bairro',
                'descricao' => 'Rota que liga o centro da cidade ao bairro residencial',
                'origem' => 'Centro',
                'destino' => 'Bairro',
                'horario_inicio' => '08:00',
                'horario_fim' => '18:00',
                'tipo' => 'Escolar',
                'distancia_km' => 15.5,
                'tempo_estimado_minutos' => 45,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Rota Escola Municipal',
                'descricao' => 'Rota para a Escola Municipal',
                'origem' => 'Terminal',
                'destino' => 'Escola Municipal',
                'horario_inicio' => '07:00',
                'horario_fim' => '17:30',
                'tipo' => 'Escolar',
                'distancia_km' => 8.2,
                'tempo_estimado_minutos' => 30,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Rota Escola Estadual',
                'descricao' => 'Rota para a Escola Estadual',
                'origem' => 'Terminal',
                'destino' => 'Escola Estadual',
                'horario_inicio' => '06:30',
                'horario_fim' => '17:00',
                'tipo' => 'Escolar',
                'distancia_km' => 12.0,
                'tempo_estimado_minutos' => 40,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
