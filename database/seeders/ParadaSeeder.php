<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParadaSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('paradas')->truncate();
        
        // Insert sample data
        DB::table('paradas')->insert([
            [
                'nome' => 'Escola Municipal',
                'endereco' => 'Rua da Educação, 100',
                'ponto_referencia' => 'Em frente à praça',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
                'tipo' => 'Inicio',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Praça Central',
                'endereco' => 'Av. Paulista, 500',
                'ponto_referencia' => 'Próximo ao metrô',
                'latitude' => -23.5630,
                'longitude' => -46.6543,
                'tipo' => 'Intermediaria',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Parque Municipal',
                'endereco' => 'Rua das Árvores, 200',
                'ponto_referencia' => 'Entrada principal',
                'latitude' => -23.5755,
                'longitude' => -46.6424,
                'tipo' => 'Intermediaria',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Centro Comercial',
                'endereco' => 'Av. Brasil, 1000',
                'ponto_referencia' => 'Em frente ao shopping',
                'latitude' => -23.5830,
                'longitude' => -46.6382,
                'tipo' => 'Intermediaria',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Bairro Residencial',
                'endereco' => 'Rua dos Jardins, 300',
                'ponto_referencia' => 'Próximo ao supermercado',
                'latitude' => -23.5905,
                'longitude' => -46.6290,
                'tipo' => 'Final',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
