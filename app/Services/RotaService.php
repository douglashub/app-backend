<?php

namespace App\Services;

use App\Models\Rota;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RotaService
{
    public function getAllRotas(): Collection
    {
        try {
            return Rota::all();
        } catch (\Exception $e) {
            Log::error('Erro ao buscar todas as rotas: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function getRotaById(int $id): ?Rota
    {
        try {
            return Rota::find($id);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar rota por ID: ' . $e->getMessage(), ['id' => $id]);
            return null;
        }
    }

    public function createRota(array $data): Rota
    {
        try {
            // Set default values if not provided
            $data['tipo'] = $data['tipo'] ?? 'Escolar';
            $data['status'] = $data['status'] ?? true;
            
            // Garante que os campos de hora estão no formato correto
            $data = $this->ensureTimeFormat($data);
        
            return Rota::create($data);
        } catch (\Exception $e) {
            Log::error('Erro ao criar rota: ' . $e->getMessage(), ['data' => $data]);
            throw new \Exception('Não foi possível criar a rota: ' . $e->getMessage());
        }
    }

    public function updateRota(int $id, array $data): ?Rota
    {
        try {
            $rota = $this->getRotaById($id);
            if (!$rota) {
                return null;
            }
            
            // Garante que os campos de hora estão no formato correto
            $data = $this->ensureTimeFormat($data);

            $rota->update($data);
            return $rota->fresh();
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar rota: ' . $e->getMessage(), ['id' => $id, 'data' => $data]);
            throw new \Exception('Não foi possível atualizar a rota: ' . $e->getMessage());
        }
    }

    public function deleteRota(int $id): bool
    {
        return DB::transaction(function() use ($id) {
            try {
                $rota = $this->getRotaById($id);
                if (!$rota) {
                    return false;
                }

                // Verificar dependências antes de tentar excluir
                if ($rota->viagens()->count() > 0) {
                    throw new \Exception('Esta rota possui viagens vinculadas e não pode ser excluída');
                }

                // Remover relações com paradas antes de excluir a rota
                if ($rota->paradas()->count() > 0) {
                    $rota->paradas()->detach();
                }

                return $rota->delete();
            } catch (\Exception $e) {
                Log::error('Erro ao excluir rota: ' . $e->getMessage(), ['id' => $id]);
                throw $e;
            }
        });
    }

    public function getRotaParadas(int $id): Collection
    {
        try {
            $rota = $this->getRotaById($id);
            if (!$rota) {
                return collect([]);
            }

            return $rota->paradas()->orderBy('rota_parada.ordem')->get();
        } catch (\Exception $e) {
            Log::error('Erro ao buscar paradas da rota: ' . $e->getMessage(), ['id' => $id]);
            return collect([]);
        }
    }

    public function getRotaViagens(int $id): Collection
    {
        try {
            $rota = $this->getRotaById($id);
            if (!$rota) {
                return collect([]);
            }

            return $rota->viagens()->orderBy('data_viagem', 'desc')->get();
        } catch (\Exception $e) {
            Log::error('Erro ao buscar viagens da rota: ' . $e->getMessage(), ['id' => $id]);
            return collect([]);
        }
    }
    
    /**
     * Garante que todos os campos de hora estejam no formato correto
     *
     * @param array $data
     * @return array
     */
    private function ensureTimeFormat(array $data): array
    {
        $timeFields = [
            'horario_inicio',
            'horario_fim'
        ];
        
        foreach ($timeFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $time = $data[$field];
                if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
                    $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $data[$field] = "{$hours}:{$matches[2]}";
                }
            }
        }
        
        return $data;
    }
}