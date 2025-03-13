<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlunoSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('alunos')->truncate();
        
        // Insert sample data
        DB::table('alunos')->insert([
            [
                'nome' => 'Maria Silva',
                'descricao' => 'Aluna do 5º ano',
                'data_nascimento' => '2015-03-15',
                'responsavel' => 'João Silva',
                'telefone_responsavel' => '+5511999999999',
                'endereco' => 'Rua das Flores, 123',
                'ponto_referencia' => 'Próximo ao supermercado',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Pedro Santos',
                'descricao' => 'Aluno do 3º ano',
                'data_nascimento' => '2017-05-20',
                'responsavel' => 'Ana Santos',
                'telefone_responsavel' => '+5511988888888',
                'endereco' => 'Av. Brasil, 456',
                'ponto_referencia' => 'Em frente à farmácia',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Luiza Oliveira',
                'descricao' => 'Aluna do 8º ano',
                'data_nascimento' => '2012-07-10',
                'responsavel' => 'Carlos Oliveira',
                'telefone_responsavel' => '+5511977777777',
                'endereco' => 'Rua dos Pinheiros, 789',
                'ponto_referencia' => 'Próximo à padaria',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Rafael Costa',
                'descricao' => 'Aluno do 6º ano',
                'data_nascimento' => '2014-11-25',
                'responsavel' => 'Mariana Costa',
                'telefone_responsavel' => '+5511966666666',
                'endereco' => 'Av. Paulista, 234',
                'ponto_referencia' => 'Ao lado do banco',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Julia Ferreira',
                'descricao' => 'Aluna do 4º ano',
                'data_nascimento' => '2016-09-30',
                'responsavel' => 'Roberto Ferreira',
                'telefone_responsavel' => '+5511955555555',
                'endereco' => 'Rua Augusta, 567',
                'ponto_referencia' => 'Próximo ao shopping',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
