<?php

namespace App\Http\Controllers;

use App\Services\RotaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RotaController extends Controller
{
    protected $service;

    public function __construct(RotaService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $rotas = $this->service->getAllRotas();
        return response()->json($rotas);
    }

    public function show(int $id): JsonResponse
    {
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            return response()->json(['message' => 'Rota não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($rota);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'origem' => 'required|string|max:255',
            'destino' => 'required|string|max:255',
            'horario_inicio' => 'required|date_format:H:i',
            'horario_fim' => 'required|date_format:H:i|after:horario_inicio',
            'status' => 'required|boolean'
        ]);

        $rota = $this->service->createRota($validatedData);
        return response()->json($rota, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'origem' => 'sometimes|string|max:255',
            'destino' => 'sometimes|string|max:255',
            'horario_inicio' => 'sometimes|date_format:H:i',
            'horario_fim' => 'sometimes|date_format:H:i|after:horario_inicio',
            'status' => 'sometimes|boolean'
        ]);

        $rota = $this->service->updateRota($id, $validatedData);
        if (!$rota) {
            return response()->json(['message' => 'Rota não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($rota);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteRota($id);
        if (!$deleted) {
            return response()->json(['message' => 'Rota não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getParadas($id)
    {
        return response()->json($this->service->getRotaParadas($id));
    }

    public function getViagens($id)
    {
        return response()->json($this->service->getRotaViagens($id));
    }
}
