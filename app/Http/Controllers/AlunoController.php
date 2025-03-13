<?php

namespace App\Http\Controllers;

use App\Services\AlunoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AlunoController extends Controller
{
    protected $alunoService;

    public function __construct(AlunoService $alunoService)
    {
        $this->alunoService = $alunoService;
    }

    public function index(): JsonResponse
    {
        $alunos = $this->alunoService->getAllAlunos();
        return response()->json($alunos);
    }

    public function show(int $id): JsonResponse
    {
        $aluno = $this->alunoService->getAlunoById($id);
        if (!$aluno) {
            return response()->json(['message' => 'Aluno não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($aluno);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_nascimento' => 'required|date',
            'responsavel' => 'required|string|max:255',
            'telefone_responsavel' => 'required|string|max:20',
            'endereco' => 'required|string',
            'ponto_referencia' => 'nullable|string',
            'status' => 'required|boolean'
        ]);

        $aluno = $this->alunoService->createAluno($validatedData);
        return response()->json($aluno, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'data_nascimento' => 'sometimes|date',
            'responsavel' => 'sometimes|string|max:255',
            'telefone_responsavel' => 'sometimes|string|max:20',
            'endereco' => 'sometimes|string',
            'ponto_referencia' => 'nullable|string',
            'status' => 'sometimes|boolean'
        ]);

        $aluno = $this->alunoService->updateAluno($id, $validatedData);
        if (!$aluno) {
            return response()->json(['message' => 'Aluno não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($aluno);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->alunoService->deleteAluno($id);
        if (!$deleted) {
            return response()->json(['message' => 'Aluno não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function presencas(int $id): JsonResponse
    {
        $aluno = $this->alunoService->getAlunoById($id);
        if (!$aluno) {
            return response()->json(['message' => 'Aluno não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $presencas = $this->alunoService->getAlunoPresencas($id);
        return response()->json($presencas);
    }
}
