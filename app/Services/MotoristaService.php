<?php

namespace App\Services;

use App\Models\Motorista;
use Illuminate\Database\Eloquent\Collection;

class MotoristaService
{
    public function getAllMotoristas(): Collection
    {
        return Motorista::all();
    }

    public function getMotoristaById(int $id): ?Motorista
    {
        return Motorista::find($id);
    }

    public function createMotorista(array $data): Motorista
    {
        return Motorista::create($data);
    }

    public function updateMotorista(int $id, array $data): ?Motorista
    {
        $motorista = $this->getMotoristaById($id);
        if (!$motorista) {
            return null;
        }

        $motorista->update($data);
        return $motorista->fresh();
    }

    public function deleteMotorista(int $id): bool
    {
        $motorista = $this->getMotoristaById($id);
        if (!$motorista) {
            return false;
        }

        return $motorista->delete();
    }

    public function getMotoristaViagens(int $id): Collection
    {
        $motorista = $this->getMotoristaById($id);
        if (!$motorista) {
            return collect([]);
        }

        return $motorista->viagens;
    }
}