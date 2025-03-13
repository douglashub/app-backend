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
        try {
            $this->loggingService->logInfo('Creating new parada');
            $validatedData = $request->validate([
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'ponto_referencia' => 'nullable|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'endereco' => 'required|string',
                'tipo' => 'required|in:Inicio,Intermediaria,Final',
                'status' => 'required|boolean'
            ]);

            $parada = $this->paradaService->createParada($validatedData);
            $this->loggingService->logInfo('Parada created', ['id' => $parada->id]);

            $response = [
                'data' => $parada,
                '_links' => $this->hateoasService->generateLinks('paradas', $parada->id)
            ];

            return response()->json($response, Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('paradas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('paradas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating parada', ['id' => $id]);
            $validatedData = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'descricao' => 'nullable|string',
                'ponto_referencia' => 'nullable|string',
                'latitude' => 'sometimes|numeric',
                'longitude' => 'sometimes|numeric',
                'endereco' => 'sometimes|string',
                'tipo' => 'sometimes|in:Inicio,Intermediaria,Final',
                'status' => 'sometimes|boolean'
            ]);

            $parada = $this->paradaService->updateParada($id, $validatedData);
            if (!$parada) {
                $this->loggingService->logError('Parada update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Parada não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('paradas')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Parada updated successfully', ['id' => $id]);
            $response = [
                'data' => $parada,
                '_links' => $this->hateoasService->generateLinks('paradas', $id)
            ];

            return response()->json($response);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('paradas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('paradas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting parada', ['id' => $id]);
            $result = $this->paradaService->deleteParada($id);
            if (!$result) {
                $this->loggingService->logError('Parada deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Parada não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('paradas')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Parada deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('paradas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}