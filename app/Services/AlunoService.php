<?php

namespace App\Services;

use App\Models\Aluno;
use Illuminate\Database\Eloquent\Collection;

class AlunoService
{
    public function getAllAlunos(): Collection
    {
        return Aluno::all();
    }

    public function getAlunoById(int $id): ?Aluno
    {
        return Aluno::find($id);
    }

    public function createAluno(array $data): Aluno
    {
        return Aluno::create($data);
    }

    public function updateAluno(int $id, array $data): ?Aluno
    {
        $aluno = $this->getAlunoById($id);
        if (!$aluno) {
            return null;
        }

        $aluno->update($data);
        return $aluno->fresh();
    }

    public function deleteAluno(int $id): bool
    {
        $aluno = $this->getAlunoById($id);
        if (!$aluno) {
            return false;
        }

        return $aluno->delete();
    }

    public function getAlunoPresencas(int $id): Collection
    {
        $aluno = $this->getAlunoById($id);
        if (!$aluno) {
            return collect([]);
        }

        return $aluno->presencas;
    }
}