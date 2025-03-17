<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AlunoSeeder::class,
            MotoristaSeeder::class,
            MonitorSeeder::class,
            OnibusSeeder::class,
            ParadaSeeder::class,
            RotaSeeder::class,
            RotaParadaSeeder::class,
            HorarioSeeder::class,
            ViagemSeeder::class,
            PresencaSeeder::class,
            CargoSeeder::class,
        ]);
    }
}