<?php

namespace App\Http\Controllers;

use App\Services\MotoristaService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MotoristaController extends Controller
{
    protected $motoristaService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(MotoristaService $motoristaService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->motoristaService = $motoristaService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all motoristas');
        $motoristas = $this->motoristaService->getAllMotoristas();
        $response = [
            'data' => $motoristas,
            '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching motorista', ['id' => $id]);
        $motorista = $this->motoristaService->getMotoristaById($id);
        if (!$motorista) {
            $this->loggingService->logError('Motorista not found', ['id' => $id]);
            return response()->json(['message' => 'Motorista não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $motorista,
            '_links' => $this->hateoasService->generateLinks('motoristas', $id)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new motorista');
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
            $this->loggingService->logInfo('Motorista created', ['id' => $motorista->id]);

            return response()->json([
                'data' => $motorista,
                '_links' => $this->hateoasService->generateLinks('motoristas', $motorista->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating motorista', ['id' => $id]);
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
                $this->loggingService->logError('Motorista update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Motorista não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Motorista updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $motorista,
                '_links' => $this->hateoasService->generateLinks('motoristas', $id)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting motorista', ['id' => $id]);
            $deleted = $this->motoristaService->deleteMotorista($id);
            if (!$deleted) {
                $this->loggingService->logError('Motorista deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Motorista não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Motorista deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('motoristas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function viagens(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching motorista viagens', ['id' => $id]);
        $motorista = $this->motoristaService->getMotoristaById($id);
        if (!$motorista) {
            $this->loggingService->logError('Motorista not found', ['id' => $id]);
            return response()->json(['message' => 'Motorista não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->motoristaService->getMotoristaViagens($id);
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateLinks('motoristas', $id)
        ]);
    }
}