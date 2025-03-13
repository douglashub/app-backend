<?php

namespace App\Http\Controllers;

use App\Services\MonitorService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonitorController extends Controller
{
    protected $monitorService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(MonitorService $monitorService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->monitorService = $monitorService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all monitores');
        $monitores = $this->monitorService->getAllMonitores();
        $response = [
            'data' => $monitores->items(),
            'meta' => [
                'current_page' => $monitores->currentPage(),
                'per_page' => $monitores->perPage(),
                'total' => $monitores->total(),
                'last_page' => $monitores->lastPage()
            ],
            '_links' => $this->hateoasService->generateCollectionLinks('monitores', $monitores)
        ];
        return response()->json($response);
    }
    
    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching monitor', ['id' => $id]);
        $monitor = $this->monitorService->getMonitorById($id);
        if (!$monitor) {
            $this->loggingService->logError('Monitor not found', ['id' => $id]);
            return response()->json(['message' => 'Monitor não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $monitor,
            '_links' => $this->hateoasService->generateLinks('monitores', $id)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new monitor');
            $validatedData = $request->validate([
                'nome' => 'required|string|max:255',
                'cpf' => 'required|string|max:14|unique:monitores,cpf',
                'telefone' => 'required|string|max:20',
                'endereco' => 'required|string',
                'data_contratacao' => 'required|date',
                'status' => 'required|in:Ativo,Ferias,Licenca,Inativo'
            ]);

            $monitor = $this->monitorService->createMonitor($validatedData);
            $this->loggingService->logInfo('Monitor created', ['id' => $monitor->id]);

            return response()->json([
                'data' => $monitor,
                '_links' => $this->hateoasService->generateLinks('monitores', $monitor->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('monitores')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('monitores')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating monitor', ['id' => $id]);
            $validatedData = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'cpf' => 'sometimes|string|max:14|unique:monitores,cpf,'.$id,
                'telefone' => 'sometimes|string|max:20',
                'endereco' => 'sometimes|string',
                'data_contratacao' => 'sometimes|date',
                'status' => 'sometimes|in:Ativo,Ferias,Licenca,Inativo'
            ]);

            $monitor = $this->monitorService->updateMonitor($id, $validatedData);
            if (!$monitor) {
                $this->loggingService->logError('Monitor update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Monitor não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('monitores')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Monitor updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $monitor,
                '_links' => $this->hateoasService->generateLinks('monitores', $id)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('monitores')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('monitores')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting monitor', ['id' => $id]);
            $deleted = $this->monitorService->deleteMonitor($id);
            if (!$deleted) {
                $this->loggingService->logError('Monitor deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Monitor não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('monitores')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Monitor deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('monitores')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function viagens(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching monitor viagens', ['id' => $id]);
        $monitor = $this->monitorService->getMonitorById($id);
        if (!$monitor) {
            $this->loggingService->logError('Monitor not found', ['id' => $id]);
            return response()->json(['message' => 'Monitor não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->monitorService->getMonitorViagens($id);
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateLinks('monitores', $id)
        ]);
    }
}