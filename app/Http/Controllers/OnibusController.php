<?php

namespace App\Http\Controllers;

use App\Services\OnibusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class OnibusController extends Controller
{
    protected $onibusService;

    public function __construct(OnibusService $onibusService)
    {
        $this->onibusService = $onibusService;
    }

    public function index(): JsonResponse
    {
        $onibus = $this->onibusService->getAllOnibus();
        return response()->json($onibus);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'placa' => 'required|string|max:7',
            'modelo' => 'required|string|max:255',
            'capacidade' => 'required|integer',
            'status' => 'required|boolean'
        ]);

        $onibus = $this->onibusService->createOnibus($validatedData);
        return response()->json($onibus, Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        $onibus = $this->onibusService->getOnibusById($id);
        if (!$onibus) {
            return response()->json(['message' => 'Ônibus não encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($onibus);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'placa' => 'sometimes|string|max:7',
            'modelo' => 'sometimes|string|max:255',
            'capacidade' => 'sometimes|integer',
            'status' => 'sometimes|boolean'
        ]);

        $onibus = $this->onibusService->updateOnibus($id, $validatedData);
        if (!$onibus) {
            return response()->json(['message' => 'Ônibus não encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($onibus);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->onibusService->deleteOnibus($id);
        if (!$deleted) {
            return response()->json(['message' => 'Ônibus não encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function viagens(int $id): JsonResponse
    {
        $onibus = $this->onibusService->getOnibusById($id);
        if (!$onibus) {
            return response()->json(['message' => 'Ônibus não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->onibusService->getOnibusViagens($id);
        return response()->json($viagens);
    }
}
