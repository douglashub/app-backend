<?php

namespace App\Services;

use App\Models\Viagem;
use Illuminate\Database\Eloquent\Collection;

class ViagemService
{
    public function getAllViagens(): Collection
    {
        return Viagem::all();
    }

    public function getViagemById(int $id): ?Viagem
    {
        return Viagem::find($id);
    }

    public function createViagem(array $data): Viagem
    {
        try {
            // Make sure all required fields are included
            $requiredFields = [
                'data_viagem',
                'rota_id',
                'onibus_id',
                'motorista_id',
                'horario_id',
                'hora_saida_prevista',
                'status'
            ];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Missing required field: {$field}");
                }
            }
            
            // Certifique-se de que todos os campos de hora estão corretamente formatados
            $data = $this->ensureTimeFormat($data);
            
            return Viagem::create($data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create viagem: ' . $e->getMessage());
        }
    }

    public function updateViagem(int $id, array $data): ?Viagem
    {
        $viagem = $this->getViagemById($id);
        if (!$viagem) {
            return null;
        }
    
        try {
            // Remove null values to prevent overwriting existing data
            $filteredData = array_filter($data, function($value) {
                return $value !== null;
            });
    
            // Certifique-se de que todos os campos de hora estão corretamente formatados
            $filteredData = $this->ensureTimeFormat($filteredData);
    
            $viagem->update($filteredData);
            return $viagem->fresh();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update viagem: ' . $e->getMessage());
        }
    }

    public function deleteViagem(int $id): bool
    {
        $viagem = $this->getViagemById($id);
        if (!$viagem) {
            return false;
        }

        return $viagem->delete();
    }

    public function getViagemPresencas(int $id): Collection
    {
        $viagem = $this->getViagemById($id);
        if (!$viagem) {
            return collect([]);
        }

        return $viagem->presencas;
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
            'hora_saida_prevista',
            'hora_chegada_prevista',
            'hora_saida_real',
            'hora_chegada_real'
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