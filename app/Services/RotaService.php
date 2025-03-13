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
        return Rota::create($data);
    }

    public function updateRota(int $id, array $data): ?Rota
    {
        $rota = $this->getRotaById($id);
        if (!$rota) {
            return null;
        }

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
}