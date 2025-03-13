<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViagemSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('viagens')->truncate();
        
        // Get tomorrow's date
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        
        // Insert sample data
        DB::table('viagens')->insert([
            [
                'data_viagem' => $today,
                'rota_id' => 1,
                'onibus_id' => 1,
                'motorista_id' => 1,
                'monitor_id' => 1,
                'horario_id' => 1,
                'hora_saida_prevista' => '07:00',
                'hora_chegada_prevista' => '07:45',
                'hora_saida_real' => '07:05',
                'hora_chegada_real' => '07:50',
                'status' => true,
                'observacoes' => 'Viagem concluída sem incidentes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'data_viagem' => $today,
                'rota_id' => 2,
                'onibus_id' => 2,
                'motorista_id' => 2,
                'monitor_id' => 2,
                'horario_id' => 3,
                'hora_saida_prevista' => '06:45',
                'hora_chegada_prevista' => '07:30',
                'hora_saida_real' => '06:50',
                'hora_chegada_real' => '07:35',
                'status' => true,
                'observacoes' => 'Atraso de 5 minutos devido ao trânsito',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'data_viagem' => $tomorrow,
                'rota_id' => 1,
                'onibus_id' => 1,
                'motorista_id' => 1,
                'monitor_id' => 1,
                'horario_id' => 1,
                'hora_saida_prevista' => '07:00',
                'hora_chegada_prevista' => '07:45',
                'hora_saida_real' => null,
                'hora_chegada_real' => null,
                'status' => true,
                'observacoes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'data_viagem' => $tomorrow,
                'rota_id' => 3,
                'onibus_id' => 3,
                'motorista_id' => 3,
                'monitor_id' => 3,
                'horario_id' => 4,
                'hora_saida_prevista' => '06:30',
                'hora_chegada_prevista' => '07:15',
                'hora_saida_real' => null,
                'hora_chegada_real' => null,
                'status' => true,
                'observacoes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
