<?php

namespace App\Services;

use App\Models\Horario;
use Illuminate\Database\Eloquent\Collection;

class HorarioService
{
    public function getAllHorarios(): Collection
    {
        return Horario::all();
    }

    public function getHorarioById(int $id): ?Horario
    {
        return Horario::find($id);
    }

    public function createHorario(array $data): Horario
    {
        return Horario::create($data);
    }

    public function updateHorario(int $id, array $data): ?Horario
    {
        $horario = $this->getHorarioById($id);
        if (!$horario) {
            return null;
        }

        $horario->update($data);
        return $horario->fresh();
    }

    public function deleteHorario(int $id): bool
    {
        $horario = $this->getHorarioById($id);
        if (!$horario) {
            return false;
        }

        return $horario->delete();
    }

    public function getHorarioViagens(int $id): Collection
    {
        $horario = $this->getHorarioById($id);
        if (!$horario) {
            return collect([]);
        }

        return $horario->viagens;
    }
}