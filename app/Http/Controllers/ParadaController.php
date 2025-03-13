<?php

namespace App\Http\Controllers;

use App\Services\ParadaService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class ParadaController extends Controller
{
    protected $paradaService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(ParadaService $paradaService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->paradaService = $paradaService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all paradas');
        $paradas = $this->paradaService->getAllParadas();
        $response = [
            'data' => $paradas,
            '_links' => $this->hateoasService->generateCollectionLinks('paradas')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching parada', ['id' => $id]);
        $parada = $this->paradaService->getParadaById($id);
        if (!$parada) {
            $this->loggingService->logError('Parada not found', ['id' => $id]);
            return response()->json(['message' => 'Parada não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $parada,
            '_links' => $this->hateoasService->generateLinks('paradas', $id)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'ponto_referencia' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'endereco' => 'required|string',
            'status' => 'required|boolean'
        ]);

        $parada = $this->paradaService->createParada($validatedData);
        $response = [
            'data' => $parada,
            '_links' => $this->hateoasService->generateLinks('paradas', $parada->id)
        ];

        return response()->json($response, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'ponto_referencia' => 'nullable|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'endereco' => 'sometimes|string',
            'status' => 'sometimes|boolean'
        ]);

        $parada = $this->paradaService->updateParada($id, $validatedData);
        if (!$parada) {
            return response()->json(['message' => 'Parada não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $parada,
            '_links' => $this->hateoasService->generateLinks('paradas', $id)
        ];

        return response()->json($response);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->paradaService->deleteParada($id);
        if (!$result) {
            return response()->json(['message' => 'Parada não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
