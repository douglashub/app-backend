<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Motorista;
use App\Models\Monitor;

class CargoSeeder extends Seeder
{
    /**
     * Run the database seeds to populate the cargo field.
     */
    public function run(): void
    {
        // Cargos disponíveis
        $cargos = ['Efetivo', 'ACT', 'Temporário'];
        
        // Atualizar motoristas
        $motoristas = Motorista::all();
        foreach ($motoristas as $motorista) {
            // Atribuir cargo aleatório para fins de demonstração
            $motorista->cargo = $cargos[array_rand($cargos)];
            $motorista->save();
        }
        
        $this->command->info('Motoristas atualizados com cargo.');
        
        // Atualizar monitores
        $monitores = Monitor::all();
        foreach ($monitores as $monitor) {
            // Atribuir cargo aleatório para fins de demonstração
            $monitor->cargo = $cargos[array_rand($cargos)];
            $monitor->save();
        }
        
        $this->command->info('Monitores atualizados com cargo.');
    }
}