<?php

namespace App\Services;

use App\Models\Rota;
use Illuminate\Database\Eloquent\Collection;

class RotaService
{
    public function getAllRotas(): Collection
    {
        return Rota::all();
    }

    public function getRotaById(int $id): ?Rota
    {
        return Rota::find($id);
    }

    public function createRota(array $data): Rota
    {
        // Set default values if not provided
        $data['tipo'] = $data['tipo'] ?? '';
        $data['status'] = $data['status'] ?? true;
        
        // Garante que os campos de hora estão no formato correto
        $data = $this->ensureTimeFormat($data);
    
        return Rota::create($data);
    }

    public function updateRota(int $id, array $data): ?Rota
    {
        $rota = $this->getRotaById($id);
        if (!$rota) {
            return null;
        }
        
        // Garante que os campos de hora estão no formato correto
        $data = $this->ensureTimeFormat($data);

        $rota->update($data);
        return $rota->fresh();
    }

    public function deleteRota(int $id): bool
    {
        $rota = $this->getRotaById($id);
        if (!$rota) {
            return false;
        }

        return $rota->delete();
    }

    public function getRotaParadas(int $id): Collection
    {
        $rota = $this->getRotaById($id);
        if (!$rota) {
            return collect([]);
        }

        return $rota->paradas;
    }

    public function getRotaViagens(int $id): Collection
    {
        $rota = $this->getRotaById($id);
        if (!$rota) {
            return collect([]);
        }

        return $rota->viagens;
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