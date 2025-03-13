<?php

namespace App\Http\Controllers;

use App\Services\ViagemService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ViagemController extends Controller
{
    protected $service;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(ViagemService $service, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->service = $service;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all viagens');
        $viagens = $this->service->getAllViagens();
        $response = [
            'data' => $viagens,
            '_links' => $this->hateoasService->generateCollectionLinks('viagens')
        ];
        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new viagem');
            $validatedData = $request->validate([
                'data_viagem' => 'required|date',
                'rota_id' => 'required|integer|exists:rotas,id',
                'onibus_id' => 'required|integer|exists:onibus,id',
                'motorista_id' => 'required|integer|exists:motoristas,id',
                'monitor_id' => 'required|integer|exists:monitores,id',
                'status' => 'required|boolean'
            ]);

            $viagem = $this->service->createViagem($validatedData);
            $this->loggingService->logInfo('Viagem created', ['id' => $viagem->id]);
            $relationships = [
                'rota' => $viagem->rota_id,
                'onibus' => $viagem->onibus_id,
                'motorista' => $viagem->motorista_id,
                'monitor' => $viagem->monitor_id
            ];

            return response()->json([
                'data' => $viagem,
                '_links' => $this->hateoasService->generateLinks('viagens', $viagem->id, $relationships)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Add similar logging to show, update and destroy methods
    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching viagem', ['id' => $id]);
        $viagem = $this->service->getViagemById($id);
        if (!$viagem) {
            $this->loggingService->logError('Viagem not found', ['id' => $id]);
            return response()->json(['message' => 'Viagem não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $relationships = [
            'rotas' => $viagem->rota_id,
            'onibus' => $viagem->onibus_id,
            'motoristas' => $viagem->motorista_id,
            'monitores' => $viagem->monitor_id
        ];

        $response = [
            'data' => $viagem,
            '_links' => $this->hateoasService->generateLinks('viagens', $id, $relationships)
        ];

        return response()->json($response);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'data_viagem' => 'required|date',
                'rota_id' => 'required|integer|exists:rotas,id',
                'onibus_id' => 'required|integer|exists:onibus,id',
                'motorista_id' => 'required|integer|exists:motoristas,id',
                'monitor_id' => 'required|integer|exists:monitores,id',
                'status' => 'required|boolean'
            ]);

            $viagem = $this->service->updateViagem($id, $validatedData);
            if (!$viagem) {
                $this->loggingService->logError('Viagem update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Viagem não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('viagens')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Viagem updated successfully', ['id' => $id]);
            return response()->json($viagem);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteViagem($id);
            if (!$deleted) {
                $this->loggingService->logError('Viagem deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Viagem não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('viagens')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Viagem deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}