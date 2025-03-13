<?php

namespace App\Services;

use App\Models\Parada;
use Illuminate\Database\Eloquent\Collection;

class ParadaService
{
    public function getAllParadas(): Collection
    {
        return Parada::all();
    }

    public function getParadaById(int $id): ?Parada
    {
        return Parada::find($id);
    }

    public function createParada(array $data): Parada
    {
        return Parada::create($data);
    }

    public function updateParada(int $id, array $data): ?Parada
    {
        $parada = $this->getParadaById($id);
        if (!$parada) {
            return null;
        }

        $parada->update($data);
        return $parada->fresh();
    }

    public function deleteParada(int $id): bool
    {
        $parada = $this->getParadaById($id);
        if (!$parada) {
            return false;
        }

        return $parada->delete();
    }

    public function getParadaRotas(int $id): Collection
    {
        $parada = $this->getParadaById($id);
        if (!$parada) {
            return collect([]);
        }

        return $parada->rotas;
    }
}