<?php

namespace App\Http\Controllers;

use App\Services\RotaService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RotaController extends Controller
{
    protected $service;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(RotaService $service, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->service = $service;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all rotas');
        $rotas = $this->service->getAllRotas();
        $response = [
            'data' => $rotas,
            '_links' => $this->hateoasService->generateCollectionLinks('rotas')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching rota', ['id' => $id]);
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            $this->loggingService->logError('Rota not found', ['id' => $id]);
            return response()->json(['message' => 'Rota não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $rota,
            '_links' => $this->hateoasService->generateLinks('rotas', $id)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new rota');
            
            // Formatar campos de hora antes da validação
            $this->formatTimeFields($request);
            
            $validatedData = $request->validate([
                'nome' => 'required|string|max:255',
                'descricao' => 'nullable|string',
                'origem' => 'nullable|string|max:255',
                'destino' => 'nullable|string|max:255',
                'horario_inicio' => 'nullable|date_format:H:i',
                'horario_fim' => 'nullable|date_format:H:i|after_or_equal:horario_inicio',
                'tipo' => 'nullable|string|max:50',
                'distancia_km' => 'nullable|numeric',
                'tempo_estimado_minutos' => 'nullable|integer',
                'status' => 'sometimes|boolean'
            ]);
    
            $rota = $this->service->createRota($validatedData);
            $this->loggingService->logInfo('Rota created', ['id' => $rota->id]);
    
            return response()->json([
                'data' => $rota,
                '_links' => $this->hateoasService->generateLinks('rotas', $rota->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating rota', ['id' => $id]);
            
            // Formatar campos de hora antes da validação
            $this->formatTimeFields($request);
            
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
                $this->loggingService->logError('Rota update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Rota não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('rotas')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Rota updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $rota,
                '_links' => $this->hateoasService->generateLinks('rotas', $id)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting rota', ['id' => $id]);
            $deleted = $this->service->deleteRota($id);
            if (!$deleted) {
                $this->loggingService->logError('Rota deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Rota não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('rotas')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Rota deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getParadas(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching rota paradas', ['id' => $id]);
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            $this->loggingService->logError('Rota not found', ['id' => $id]);
            return response()->json(['message' => 'Rota não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $paradas = $this->service->getRotaParadas($id);
        return response()->json([
            'data' => $paradas,
            '_links' => $this->hateoasService->generateLinks('rotas', $id)
        ]);
    }

    public function getViagens(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching rota viagens', ['id' => $id]);
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            $this->loggingService->logError('Rota not found', ['id' => $id]);
            return response()->json(['message' => 'Rota não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->service->getRotaViagens($id);
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateLinks('rotas', $id)
        ]);
    }
    
    /**
     * Formata os campos de hora para garantir que estejam no padrão H:i
     * 
     * @param Request $request
     * @return void
     */
    private function formatTimeFields(Request $request): void
    {
        $timeFields = [
            'horario_inicio',
            'horario_fim'
        ];
        
        foreach ($timeFields as $field) {
            if ($request->has($field) && $request->input($field)) {
                $time = $request->input($field);
                // Verifica se o formato precisa ser ajustado (se tem apenas um dígito para hora)
                if (preg_match('/^(\d{1}):(\d{2})$/', $time, $matches)) {
                    $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $request->merge([$field => "{$hours}:{$matches[2]}"]);
                    $this->loggingService->logInfo("Formatted time field {$field} from {$time} to {$hours}:{$matches[2]}");
                }
            }
        }
    }
}