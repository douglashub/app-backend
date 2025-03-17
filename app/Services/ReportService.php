<?php

namespace App\Services;

use App\Models\Motorista;
use App\Models\Monitor;
use App\Models\Rota;
use App\Models\Horario;
use App\Models\Viagem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Gera relatório de motoristas com informações de rota e horário
     * 
     * @param array $filters Array de filtros (data, rota_id, cargo, etc)
     * @return array Dados do relatório formatados
     */
    public function getMotoristaReport(array $filters = []): array
    {
        $query = Motorista::query()
            ->select(
                'motoristas.id',
                'motoristas.nome',
                'motoristas.cpf',
                'motoristas.cargo',
                'motoristas.status',
                DB::raw('COUNT(DISTINCT viagens.id) as total_viagens')
            )
            ->leftJoin('viagens', 'motoristas.id', '=', 'viagens.motorista_id');

        // Aplicar filtros
        if (!empty($filters['cargo'])) {
            $query->where('motoristas.cargo', $filters['cargo']);
        }

        if (!empty($filters['status'])) {
            $query->where('motoristas.status', $filters['status']);
        }

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $query->whereBetween('viagens.data_viagem', [$filters['data_inicio'], $filters['data_fim']]);
        }

        if (!empty($filters['rota_id'])) {
            $query->where('viagens.rota_id', $filters['rota_id']);
        }

        $resultados = $query->groupBy(
                'motoristas.id',
                'motoristas.nome',
                'motoristas.cpf',
                'motoristas.cargo',
                'motoristas.status'
            )
            ->get();
            
        $motoristas = [];
        
        foreach ($resultados as $motorista) {
            // Buscar viagens mais recentes para o motorista
            $viagens = Viagem::with(['rota', 'horario'])
                ->where('motorista_id', $motorista->id)
                ->orderBy('data_viagem', 'desc')
                ->take(5)
                ->get();
                
            $rotas = [];
            $horarios = [];
            
            foreach ($viagens as $viagem) {
                if ($viagem->rota) {
                    $rotas[$viagem->rota->id] = $viagem->rota->nome;
                }
                
                if ($viagem->horario) {
                    $horario = $viagem->horario;
                    $horarios[$horario->id] = "Das {$horario->hora_inicio} às {$horario->hora_fim}";
                }
            }
            
            $motoristas[] = [
                'id' => $motorista->id,
                'nome' => $motorista->nome,
                'cpf' => $motorista->cpf,
                'cargo' => $motorista->cargo,
                'status' => $motorista->status,
                'total_viagens' => $motorista->total_viagens,
                'rotas' => array_values($rotas),
                'horarios' => array_values($horarios)
            ];
        }
        
        return [
            'data' => $motoristas,
            'total' => count($motoristas),
            'filtros' => $filters,
            'data_geracao' => Carbon::now()->format('d/m/Y H:i:s')
        ];
    }
    
    /**
     * Gera relatório de monitores com informações de rota e horário
     * 
     * @param array $filters Array de filtros (data, rota_id, cargo, etc)
     * @return array Dados do relatório formatados
     */
    public function getMonitorReport(array $filters = []): array
    {
        $query = Monitor::query()
            ->select(
                'monitores.id',
                'monitores.nome',
                'monitores.cpf',
                'monitores.cargo',
                'monitores.status',
                DB::raw('COUNT(DISTINCT viagens.id) as total_viagens')
            )
            ->leftJoin('viagens', 'monitores.id', '=', 'viagens.monitor_id');

        // Aplicar filtros
        if (!empty($filters['cargo'])) {
            $query->where('monitores.cargo', $filters['cargo']);
        }

        if (!empty($filters['status'])) {
            $query->where('monitores.status', $filters['status']);
        }

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $query->whereBetween('viagens.data_viagem', [$filters['data_inicio'], $filters['data_fim']]);
        }

        if (!empty($filters['rota_id'])) {
            $query->where('viagens.rota_id', $filters['rota_id']);
        }

        $resultados = $query->groupBy(
                'monitores.id',
                'monitores.nome',
                'monitores.cpf',
                'monitores.cargo',
                'monitores.status'
            )
            ->get();
            
        $monitores = [];
        
        foreach ($resultados as $monitor) {
            // Buscar viagens mais recentes para o monitor
            $viagens = Viagem::with(['rota', 'horario'])
                ->where('monitor_id', $monitor->id)
                ->orderBy('data_viagem', 'desc')
                ->take(5)
                ->get();
                
            $rotas = [];
            $horarios = [];
            
            foreach ($viagens as $viagem) {
                if ($viagem->rota) {
                    $rotas[$viagem->rota->id] = $viagem->rota->nome;
                }
                
                if ($viagem->horario) {
                    $horario = $viagem->horario;
                    $horarios[$horario->id] = "Das {$horario->hora_inicio} às {$horario->hora_fim}";
                }
            }
            
            $monitores[] = [
                'id' => $monitor->id,
                'nome' => $monitor->nome,
                'cpf' => $monitor->cpf,
                'cargo' => $monitor->cargo,
                'status' => $monitor->status,
                'total_viagens' => $monitor->total_viagens,
                'rotas' => array_values($rotas),
                'horarios' => array_values($horarios)
            ];
        }
        
        return [
            'data' => $monitores,
            'total' => count($monitores),
            'filtros' => $filters,
            'data_geracao' => Carbon::now()->format('d/m/Y H:i:s')
        ];
    }
    
    /**
     * Gera relatório de viagens por período
     * 
     * @param array $filters Array de filtros (data, rota_id, etc)
     * @return array Dados do relatório formatados
     */
    public function getViagemReport(array $filters = []): array
    {
        $query = Viagem::with(['rota', 'horario', 'motorista', 'monitor', 'onibus']);
        
        // Aplicar filtros
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $query->whereBetween('data_viagem', [$filters['data_inicio'], $filters['data_fim']]);
        }

        if (!empty($filters['rota_id'])) {
            $query->where('rota_id', $filters['rota_id']);
        }
        
        if (!empty($filters['motorista_id'])) {
            $query->where('motorista_id', $filters['motorista_id']);
        }
        
        if (!empty($filters['monitor_id'])) {
            $query->where('monitor_id', $filters['monitor_id']);
        }
        
        if (!empty($filters['onibus_id'])) {
            $query->where('onibus_id', $filters['onibus_id']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $viagens = $query->orderBy('data_viagem', 'desc')
            ->orderBy('hora_saida_prevista', 'asc')
            ->get();
            
        $resultado = [];
        
        foreach ($viagens as $viagem) {
            $resultado[] = [
                'id' => $viagem->id,
                'data' => Carbon::parse($viagem->data_viagem)->format('d/m/Y'),
                'rota' => $viagem->rota ? $viagem->rota->nome : 'N/A',
                'horario' => $viagem->horario ? "Das {$viagem->horario->hora_inicio} às {$viagem->horario->hora_fim}" : 'N/A',
                'motorista' => [
                    'nome' => $viagem->motorista ? $viagem->motorista->nome : 'N/A',
                    'cargo' => $viagem->motorista ? $viagem->motorista->cargo : 'N/A'
                ],
                'monitor' => [
                    'nome' => $viagem->monitor ? $viagem->monitor->nome : 'N/A',
                    'cargo' => $viagem->monitor ? $viagem->monitor->cargo : 'N/A'
                ],
                'onibus' => $viagem->onibus ? $viagem->onibus->placa . ' - ' . $viagem->onibus->modelo : 'N/A',
                'hora_saida_prevista' => $viagem->hora_saida_prevista,
                'hora_chegada_prevista' => $viagem->hora_chegada_prevista,
                'hora_saida_real' => $viagem->hora_saida_real,
                'hora_chegada_real' => $viagem->hora_chegada_real,
                'status' => $viagem->status ? 'Ativa' : 'Inativa',
                'observacoes' => $viagem->observacoes
            ];
        }
        
        return [
            'data' => $resultado,
            'total' => count($resultado),
            'filtros' => $filters,
            'data_geracao' => Carbon::now()->format('d/m/Y H:i:s')
        ];
    }
}