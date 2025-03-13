<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MotoristaSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('motoristas')->truncate();
        
        // Insert sample data
        DB::table('motoristas')->insert([
            [
                'nome' => 'Carlos Oliveira',
                'cpf' => '123.456.789-00',
                'cnh' => '12345678900',
                'categoria_cnh' => 'D',
                'validade_cnh' => '2026-05-20',
                'telefone' => '+5511977777777',
                'endereco' => 'Av. Paulista, 1000',
                'data_contratacao' => '2022-01-15',
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Márcia Santos',
                'cpf' => '234.567.890-11',
                'cnh' => '23456789011',
                'categoria_cnh' => 'D',
                'validade_cnh' => '2025-11-10',
                'telefone' => '+5511966666666',
                'endereco' => 'Rua Augusta, 500',
                'data_contratacao' => '2021-08-10',
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Roberto Silva',
                'cpf' => '345.678.901-22',
                'cnh' => '34567890122',
                'categoria_cnh' => 'E',
                'validade_cnh' => '2027-03-15',
                'telefone' => '+5511955555555',
                'endereco' => 'Av. Brigadeiro Faria Lima, 300',
                'data_contratacao' => '2023-02-01',
                'status' => 'Ativo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
