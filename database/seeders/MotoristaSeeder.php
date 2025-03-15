<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Motorista;
use App\Models\Viagem;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MotoristaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        // For PostgreSQL, disable triggers on the viagens table
        DB::statement('ALTER TABLE viagens DISABLE TRIGGER ALL');
        DB::statement('ALTER TABLE motoristas DISABLE TRIGGER ALL');

        try {
            // Truncate related tables
            DB::table('viagens')->truncate();
            DB::table('motoristas')->truncate();

            // Seed multiple motoristas
            $statusVariations = [
                'Ativo', 'Inativo', 'Ferias', 'Licenca',
                true, false, 1, 0, 
                'active', 'inactive', 
                'vacation', 'leave', 
                'férias', 'licença'
            ];

            // Create base motoristas
            $motoristas = collect();
            for ($i = 0; $i < 5; $i++) {
                $statusSeed = $statusVariations[array_rand($statusVariations)];

                $motorista = Motorista::create([
                    'nome' => $faker->name,
                    'cpf' => $faker->unique()->numerify('###########'),
                    'cnh' => $faker->unique()->numerify('##########'),
                    'categoria_cnh' => $faker->randomElement(['A', 'B', 'C', 'D', 'E']),
                    'validade_cnh' => Carbon::now()->addYears(rand(1, 5)),
                    'telefone' => $faker->phoneNumber(),
                    'endereco' => $faker->address(),
                    'data_contratacao' => Carbon::now()->subMonths(rand(1, 36)),
                    'status' => $statusSeed
                ]);

                $motoristas->push($motorista);
            }

            // Create a few hardcoded status edge cases
            $edgeCases = [
                ['nome' => 'João Active', 'status' => 'true'],
                ['nome' => 'Maria Inactive', 'status' => '0'],
                ['nome' => 'Pedro Vacation', 'status' => 'férias'],
                ['nome' => 'Ana Leave', 'status' => 'licença']
            ];

            foreach ($edgeCases as $case) {
                $motorista = Motorista::create([
                    'nome' => $case['nome'],
                    'cpf' => $faker->unique()->numerify('###########'),
                    'cnh' => $faker->unique()->numerify('##########'),
                    'categoria_cnh' => $faker->randomElement(['A', 'B', 'C', 'D', 'E']),
                    'validade_cnh' => Carbon::now()->addYears(rand(1, 5)),
                    'telefone' => $faker->phoneNumber(),
                    'endereco' => $faker->address(),
                    'data_contratacao' => Carbon::now()->subMonths(rand(1, 36)),
                    'status' => $case['status']
                ]);

                $motoristas->push($motorista);
            }

        } catch (\Exception $e) {
            // Log or rethrow the exception
            throw new \RuntimeException('Failed to seed motoristas: ' . $e->getMessage());
        } finally {
            // Re-enable triggers
            DB::statement('ALTER TABLE viagens ENABLE TRIGGER ALL');
            DB::statement('ALTER TABLE motoristas ENABLE TRIGGER ALL');
        }
    }
}