<?php

namespace App\Services;

use App\Models\Onibus;
use Illuminate\Database\Eloquent\Collection;

class OnibusService
{
    public function getAllOnibus(): Collection
    {
        return Onibus::all();
    }

    public function getOnibusById(int $id): ?Onibus
    {
        return Onibus::find($id);
    }

    public function createOnibus(array $data): Onibus
    {
        try {
            // Certifique-se de que todos os campos obrigatórios estão presentes
            $requiredFields = [
                'placa',
                'modelo',
                'capacidade',
                'ano_fabricacao',
                'status'
            ];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Campo obrigatório ausente: {$field}");
                }
            }
            
            return Onibus::create($data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Falha ao criar ônibus: ' . $e->getMessage());
        }
    }

    public function updateOnibus(int $id, array $data): ?Onibus
    {
        $onibus = $this->getOnibusById($id);
        if (!$onibus) {
            return null;
        }

        try {
            $onibus->update($data);
            return $onibus->fresh();
        } catch (\Exception $e) {
            throw new \RuntimeException('Falha ao atualizar ônibus: ' . $e->getMessage());
        }
    }

    public function deleteOnibus(int $id): bool
    {
        $onibus = $this->getOnibusById($id);
        if (!$onibus) {
            return false;
        }

        try {
            // Check if there are related viagens
            if ($onibus->viagens()->count() > 0) {
                throw new \Exception('Não é possível excluir o ônibus porque existem viagens associadas a ele.');
            }

            return $onibus->delete();
        } catch (\Exception $e) {
            throw new \RuntimeException('Falha ao excluir ônibus: ' . $e->getMessage());
        }
    }

    public function getOnibusViagens(int $id): Collection
    {
        $onibus = $this->getOnibusById($id);
        if (!$onibus) {
            return collect([]);
        }

        return $onibus->viagens;
    }
}