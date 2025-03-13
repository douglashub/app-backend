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
        return Onibus::create($data);
    }

    public function updateOnibus(int $id, array $data): ?Onibus
    {
        $onibus = $this->getOnibusById($id);
        if (!$onibus) {
            return null;
        }

        $onibus->update($data);
        return $onibus->fresh();
    }

    public function deleteOnibus(int $id): bool
    {
        $onibus = $this->getOnibusById($id);
        if (!$onibus) {
            return false;
        }

        return $onibus->delete();
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