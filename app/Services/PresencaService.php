<?php

namespace App\Services;

use App\Models\Presenca;
use Illuminate\Database\Eloquent\Collection;

class PresencaService
{
    public function getAllPresencas(): Collection
    {
        return Presenca::all();
    }

    public function getPresencaById(int $id): ?Presenca
    {
        return Presenca::find($id);
    }

    public function createPresenca(array $data): Presenca
    {
        return Presenca::create($data);
    }

    public function updatePresenca(int $id, array $data): ?Presenca
    {
        $presenca = $this->getPresencaById($id);
        if (!$presenca) {
            return null;
        }

        $presenca->update($data);
        return $presenca->fresh();
    }

    public function deletePresenca(int $id): bool
    {
        $presenca = $this->getPresencaById($id);
        if (!$presenca) {
            return false;
        }

        return $presenca->delete();
    }
}