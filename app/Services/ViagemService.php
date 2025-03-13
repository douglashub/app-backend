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
                'hora_chegada_prevista',
                'status'
            ];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Missing required field: {$field}");
                }
            }
            
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
            $viagem->update($data);
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
}