<?php

namespace App\Services;

use App\Models\Viagem;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

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
            // Lista de campos obrigatórios (removido horario_id)
            $requiredFields = [
                'data_viagem',
                'rota_id',
                'onibus_id',
                'motorista_id',
                'hora_saida_prevista',
                'status'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Missing required field: {$field}");
                }
            }

            // Verificar se horario_id existe, caso contrário, definir como null
            if (isset($data['horario_id']) && !Horario::where('id', $data['horario_id'])->exists()) {
                Log::warning("Horario ID inválido: " . $data['horario_id']);
                $data['horario_id'] = null;
            }

            // Certificar-se de que os horários estão corretamente formatados
            $data = $this->ensureTimeFormat($data);

            return Viagem::create($data);
        } catch (\Exception $e) {
            Log::error("Erro ao criar viagem: " . $e->getMessage());
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
            // Remover valores nulos para evitar sobrescrever dados existentes
            $filteredData = array_filter($data, function ($value) {
                return $value !== null;
            });

            // Verificar se horario_id existe, caso contrário, definir como null
            if (isset($filteredData['horario_id']) && !Horario::where('id', $filteredData['horario_id'])->exists()) {
                Log::warning("Tentativa de atualizar com horário inválido: " . $filteredData['horario_id']);
                $filteredData['horario_id'] = null;
            }

            // Certificar-se de que os horários estão corretamente formatados
            $filteredData = $this->ensureTimeFormat($filteredData);

            $viagem->update($filteredData);
            return $viagem->fresh();
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar viagem ID {$id}: " . $e->getMessage());
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
     * Garante que todos os campos de hora estejam no formato correto (HH:mm)
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
