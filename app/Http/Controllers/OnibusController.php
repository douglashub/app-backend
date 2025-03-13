<?php

namespace App\Http\Controllers;

use App\Services\OnibusService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OnibusController extends Controller
{
    protected $onibusService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(OnibusService $onibusService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->onibusService = $onibusService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all onibus');
        $onibus = $this->onibusService->getAllOnibus();
        $response = [
            'data' => $onibus,
            '_links' => $this->hateoasService->generateCollectionLinks('onibus')
        ];
        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new onibus');
            $validatedData = $request->validate([
                'placa' => 'required|string|max:10',
                'modelo' => 'required|string|max:255',
                'capacidade' => 'required|integer',
                'status' => 'required|boolean'
            ]);

            $onibus = $this->onibusService->createOnibus($validatedData);
            $this->loggingService->logInfo('Onibus created', ['id' => $onibus->id]);

            return response()->json([
                'data' => $onibus,
                '_links' => $this->hateoasService->generateLinks('onibus', $onibus->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('onibus')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('onibus')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching onibus', ['id' => $id]);
        $onibus = $this->onibusService->getOnibusById($id);
        if (!$onibus) {
            $this->loggingService->logError('Onibus not found', ['id' => $id]);
            return response()->json(['message' => 'Ônibus não encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $response = [
            'data' => $onibus,
            '_links' => $this->hateoasService->generateLinks('onibus', $id)
        ];
        
        return response()->json($response);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating onibus', ['id' => $id]);
            $validatedData = $request->validate([
                'placa' => 'sometimes|string|max:10',
                'modelo' => 'sometimes|string|max:255',
                'capacidade' => 'sometimes|integer',
                'status' => 'sometimes|boolean'
            ]);

            $onibus = $this->onibusService->updateOnibus($id, $validatedData);
            if (!$onibus) {
                $this->loggingService->logError('Onibus update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Ônibus não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('onibus')
                ], Response::HTTP_NOT_FOUND);
            }
            
            $this->loggingService->logInfo('Onibus updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $onibus,
                '_links' => $this->hateoasService->generateLinks('onibus', $id)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('onibus')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('onibus')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting onibus', ['id' => $id]);
            $deleted = $this->onibusService->deleteOnibus($id);
            if (!$deleted) {
                $this->loggingService->logError('Onibus deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Ônibus não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('onibus')
                ], Response::HTTP_NOT_FOUND);
            }
            
            $this->loggingService->logInfo('Onibus deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('onibus')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function viagens(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching onibus viagens', ['id' => $id]);
        $onibus = $this->onibusService->getOnibusById($id);
        if (!$onibus) {
            $this->loggingService->logError('Onibus not found', ['id' => $id]);
            return response()->json(['message' => 'Ônibus não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->onibusService->getOnibusViagens($id);
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateLinks('onibus', $id)
        ]);
    }
}