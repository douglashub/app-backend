<?php

namespace App\Services;

use App\Models\Motorista;
use App\Models\Monitor;
use App\Models\Rota;
use App\Models\Horario;
use App\Models\Viagem;
use Illuminate\Support\Facades\Log;
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
        try {
            $query = Viagem::with([
                'rota',
                'horario',
                'motorista',
                'monitor',
                'onibus'
            ]);

            // Add date range filtering
            if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
                $query->whereBetween('data_viagem', [$filters['data_inicio'], $filters['data_fim']]);
            }

            // Robust status filtering
            if (isset($filters['status'])) {
                // Convert various status representations to boolean
                $status = null;

                // Handle different input types
                if (is_bool($filters['status'])) {
                    $status = $filters['status'];
                } elseif (is_string($filters['status'])) {
                    $statusLower = strtolower($filters['status']);

                    $statusMap = [
                        'true' => true,
                        '1' => true,
                        'active' => true,
                        'ativo' => true,
                        'yes' => true,
                        'false' => false,
                        '0' => false,
                        'inactive' => false,
                        'inativo' => false,
                        'no' => false
                    ];

                    $status = $statusMap[$statusLower] ?? null;
                } elseif (is_numeric($filters['status'])) {
                    $status = (bool) $filters['status'];
                }

                // Apply status filter only if a valid boolean is found
                if ($status !== null) {
                    $query->where('status', $status);
                }
            }

            // Add cargo filtering for motorista and monitor
            if (!empty($filters['cargo'])) {
                $query->where(function ($q) use ($filters) {
                    $q->whereHas('motorista', function ($subQuery) use ($filters) {
                        $subQuery->where('cargo', $filters['cargo']);
                    })->orWhereHas('monitor', function ($subQuery) use ($filters) {
                        $subQuery->where('cargo', $filters['cargo']);
                    });
                });
            }

            // Execute the query
            $viagens = $query->orderBy('data_viagem', 'desc')
                ->orderBy('hora_saida_prevista', 'asc')
                ->get();

            // Transform the data
            $resultado = $viagens->map(function ($viagem) {
                return [
                    'id' => $viagem->id,
                    'data_viagem' => $viagem->data_viagem,
                    'rota' => $viagem->rota ? [
                        'id' => $viagem->rota->id,
                        'nome' => $viagem->rota->nome
                    ] : null,
                    'motorista' => $viagem->motorista ? [
                        'id' => $viagem->motorista->id,
                        'nome' => $viagem->motorista->nome,
                        'cargo' => $viagem->motorista->cargo
                    ] : null,
                    'monitor' => $viagem->monitor ? [
                        'id' => $viagem->monitor->id,
                        'nome' => $viagem->monitor->nome,
                        'cargo' => $viagem->monitor->cargo
                    ] : null,
                    'onibus' => $viagem->onibus ? [
                        'id' => $viagem->onibus->id,
                        'placa' => $viagem->onibus->placa,
                        'modelo' => $viagem->onibus->modelo
                    ] : null,
                    'hora_saida_prevista' => $viagem->hora_saida_prevista,
                    'hora_chegada_prevista' => $viagem->hora_chegada_prevista,
                    'status' => $viagem->status ? 'Ativo' : 'Inativo'
                ];
            });

            return [
                'data' => $resultado,
                'total' => $resultado->count(),
                'filtros' => $filters,
                'data_geracao' => Carbon::now()->format('d/m/Y H:i:s')
            ];
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Erro ao gerar relatório de viagens: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            // Throw a more informative exception
            throw new \Exception('Erro ao processar relatório de viagens: ' . $e->getMessage());
        }
    }
}
