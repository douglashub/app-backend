<?php

namespace App\Http\Controllers;

use App\Services\MotoristaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MotoristaController extends Controller
{
    protected $motoristaService;

    public function __construct(MotoristaService $motoristaService)
    {
        $this->motoristaService = $motoristaService;
    }


    public function index(): JsonResponse
    {
        $motoristas = $this->motoristaService->getAllMotoristas();
        return response()->json($motoristas);
    }

    public function show(int $id): JsonResponse
    {
        $motorista = $this->motoristaService->getMotoristaById($id);
        if (!$motorista) {
            return response()->json(['message' => 'Motorista não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($motorista);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|max:14',
            'cnh' => 'required|string|max:20',
            'categoria_cnh' => 'required|string|max:5',
            'validade_cnh' => 'required|date',
            'telefone' => 'required|string|max:20',
            'endereco' => 'required|string',
            'data_contratacao' => 'required|date',
            'status' => 'required|boolean'
        ]);

        $motorista = $this->motoristaService->createMotorista($validatedData);
        return response()->json($motorista, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'cpf' => 'sometimes|string|max:14',
            'cnh' => 'sometimes|string|max:20',
            'categoria_cnh' => 'sometimes|string|max:5',
            'validade_cnh' => 'sometimes|date',
            'telefone' => 'sometimes|string|max:20',
            'endereco' => 'sometimes|string',
            'data_contratacao' => 'sometimes|date',
            'status' => 'sometimes|boolean'
        ]);

        $motorista = $this->motoristaService->updateMotorista($id, $validatedData);
        if (!$motorista) {
            return response()->json(['message' => 'Motorista não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($motorista);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->motoristaService->deleteMotorista($id);
        if (!$deleted) {
            return response()->json(['message' => 'Motorista não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function viagens(int $id): JsonResponse
    {
        $motorista = $this->motoristaService->getMotoristaById($id);
        if (!$motorista) {
            return response()->json(['message' => 'Motorista não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->motoristaService->getMotoristaViagens($id);
        return response()->json($viagens);
    }
}
