<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonitorSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('monitores')->truncate();
        
        // Insert sample data
        DB::table('monitores')->insert([
            [
                'nome' => 'Ana Santos',
                'cpf' => '987.654.321-00',
                'telefone' => '+5511955555555',
                'endereco' => 'Rua Augusta, 500',
                'data_contratacao' => '2022-03-15',
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'João Ferreira',
                'cpf' => '876.543.210-11',
                'telefone' => '+5511944444444',
                'endereco' => 'Av. Paulista, 400',
                'data_contratacao' => '2021-10-05',
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Carla Lima',
                'cpf' => '765.432.109-22',
                'telefone' => '+5511933333333',
                'endereco' => 'Rua da Consolação, 300',
                'data_contratacao' => '2023-01-10',
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
