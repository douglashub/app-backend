<?php

namespace App\Http\Controllers;

use App\Services\PresencaService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PresencaController extends Controller
{
    protected $presencaService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(
        PresencaService $presencaService, 
        HateoasService $hateoasService, 
        LoggingService $loggingService
    ) {
        $this->presencaService = $presencaService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    /**
     * Retrieve all presencas
     */
    public function index(): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Fetching all presencas');
            
            $presencas = $this->presencaService->getAllPresencas();
            
            return response()->json([
                'data' => $presencas,
                'total' => $presencas->count(),
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Failed to fetch presencas', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao recuperar presenças',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieve presencas by viagem
     */
    public function getPresencasByViagem(int $viagemId): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Fetching presencas for viagem', ['viagem_id' => $viagemId]);
            
            $presencas = $this->presencaService->getPresencasByViagem($viagemId);
            
            return response()->json([
                'data' => $presencas,
                'total' => $presencas->count(),
                '_links' => $this->hateoasService->generateLinks('viagens', $viagemId)
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Failed to fetch presencas by viagem', [
                'viagem_id' => $viagemId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao recuperar presenças da viagem',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Retrieve presencas by aluno
     */
    public function getPresencasByAluno(int $alunoId): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Fetching presencas for aluno', ['aluno_id' => $alunoId]);
            
            $presencas = $this->presencaService->getPresencasByAluno($alunoId);
            
            return response()->json([
                'data' => $presencas,
                'total' => $presencas->count(),
                'presence_count' => $this->presencaService->countPresencasByAluno($alunoId),
                '_links' => $this->hateoasService->generateLinks('alunos', $alunoId)
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Failed to fetch presencas by aluno', [
                'aluno_id' => $alunoId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao recuperar presenças do aluno',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Show a specific presenca
     */
    public function show(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Fetching presenca', ['id' => $id]);
            
            $presenca = $this->presencaService->getPresencaById($id);
            
            if (!$presenca) {
                return response()->json([
                    'message' => 'Presença não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('presencas')
                ], Response::HTTP_NOT_FOUND);
            }

            $relationships = [
                'viagem' => $presenca->viagem_id,
                'aluno' => $presenca->aluno_id
            ];

            return response()->json([
                'data' => $presenca,
                '_links' => $this->hateoasService->generateLinks('presencas', $id, $relationships)
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Failed to fetch presenca', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao recuperar presença',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new presenca
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new presenca');
            
            $validatedData = $request->validate([
                'viagem_id' => 'required|integer|exists:viagens,id',
                'aluno_id' => 'required|integer|exists:alunos,id',
                'hora_embarque' => 'required|date_format:H:i',
                'presente' => 'required|boolean',
                'observacoes' => 'nullable|string|max:255'
            ]);

            $presenca = $this->presencaService->createPresenca($validatedData);
            
            $relationships = [
                'viagem' => $presenca->viagem_id,
                'aluno' => $presenca->aluno_id
            ];

            return response()->json([
                'data' => $presenca,
                '_links' => $this->hateoasService->generateLinks('presencas', $presenca->id, $relationships)
            ], Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            $this->loggingService->logError('Validation failed for presenca creation', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            $this->loggingService->logError('Error creating presenca', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao criar presença',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing presenca
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating presenca', ['id' => $id]);
            
            $validatedData = $request->validate([
                'viagem_id' => 'sometimes|required|integer|exists:viagens,id',
                'aluno_id' => 'sometimes|required|integer|exists:alunos,id',
                'hora_embarque' => 'sometimes|required|date_format:H:i',
                'presente' => 'sometimes|required|boolean',
                'observacoes' => 'nullable|string|max:255'
            ]);

            $presenca = $this->presencaService->updatePresenca($id, $validatedData);
            
            if (!$presenca) {
                return response()->json([
                    'message' => 'Presença não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('presencas')
                ], Response::HTTP_NOT_FOUND);
            }

            $relationships = [
                'viagem' => $presenca->viagem_id,
                'aluno' => $presenca->aluno_id
            ];

            return response()->json([
                'data' => $presenca,
                '_links' => $this->hateoasService->generateLinks('presencas', $id, $relationships)
            ]);

        } catch (ValidationException $e) {
            $this->loggingService->logError('Validation failed for presenca update', [
                'id' => $id,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            $this->loggingService->logError('Error updating presenca', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar presença',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a presenca
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting presenca', ['id' => $id]);
            
            $deleted = $this->presencaService->deletePresenca($id);
            
            if (!$deleted) {
                return response()->json([
                    'message' => 'Presença não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('presencas')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json(null, Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            $this->loggingService->logError('Error deleting presenca', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao excluir presença',
                '_links' => $this->hateoasService->generateCollectionLinks('presencas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}