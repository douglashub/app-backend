<?php

namespace App\Http\Controllers;

use App\Services\RotaService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;

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
        $this->loggingService->logInfo('Buscando todas as rotas');
        $rotas = $this->service->getAllRotas();
        $response = [
            'data' => $rotas,
            '_links' => $this->hateoasService->generateCollectionLinks('rotas')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Buscando rota específica', ['id' => $id]);
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            $this->loggingService->logError('Rota não encontrada', ['id' => $id]);
            return response()->json([
                'message' => 'Rota não encontrada',
                'status' => 'error'
            ], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $rota,
            '_links' => $this->hateoasService->generateLinks('rotas', $id),
            'status' => 'success'
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Criando nova rota');
            
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
            $this->loggingService->logInfo('Rota criada com sucesso', ['id' => $rota->id]);
    
            return response()->json([
                'message' => 'Rota criada com sucesso',
                'data' => $rota,
                '_links' => $this->hateoasService->generateLinks('rotas', $rota->id),
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Erro de validação: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('rotas'),
                'status' => 'error'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro no servidor: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocorreu um erro ao criar a rota',
                'detalhes' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('rotas'),
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Atualizando rota', ['id' => $id]);
            
            // Formatar campos de hora antes da validação
            $this->formatTimeFields($request);
            
            $validatedData = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'descricao' => 'nullable|string',
                'origem' => 'sometimes|string|max:255',
                'destino' => 'sometimes|string|max:255',
                'horario_inicio' => 'sometimes|date_format:H:i',
                'horario_fim' => 'sometimes|date_format:H:i|after:horario_inicio',
                'tipo' => 'sometimes|string|max:50',
                'distancia_km' => 'sometimes|numeric',
                'tempo_estimado_minutos' => 'sometimes|integer',
                'status' => 'sometimes|boolean'
            ]);

            // Log dos dados para debug
            $this->loggingService->logInfo('Dados validados para atualização', ['data' => $validatedData]);

            $rota = $this->service->updateRota($id, $validatedData);
            if (!$rota) {
                $this->loggingService->logError('Falha na atualização da rota', ['id' => $id]);
                return response()->json([
                    'message' => 'Rota não encontrada',
                    '_links' => $this->hateoasService->generateCollectionLinks('rotas'),
                    'status' => 'error'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Rota atualizada com sucesso', ['id' => $id]);
            return response()->json([
                'message' => 'Rota atualizada com sucesso',
                'data' => $rota,
                '_links' => $this->hateoasService->generateLinks('rotas', $id),
                'status' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Erro de validação: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('rotas'),
                'status' => 'error'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro no servidor: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocorreu um erro ao atualizar a rota',
                'detalhes' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('rotas'),
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Tentando excluir rota', ['id' => $id]);
            
            // Verificar primeiro se a rota existe
            $rota = $this->service->getRotaById($id);
            if (!$rota) {
                $this->loggingService->logError('Rota não encontrada para exclusão', ['id' => $id]);
                return response()->json([
                    'message' => 'Rota não encontrada',
                    'status' => 'error',
                    '_links' => $this->hateoasService->generateCollectionLinks('rotas')
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Verificar se a rota possui viagens relacionadas antes de tentar excluir
            $viagensCount = $rota->viagens()->count();
            if ($viagensCount > 0) {
                $this->loggingService->logError('Exclusão bloqueada: rota possui viagens relacionadas', [
                    'id' => $id, 
                    'viagens_count' => $viagensCount
                ]);
                
                return response()->json([
                    'message' => 'Não é possível excluir esta rota porque existem viagens associadas a ela.',
                    'detalhes' => "Esta rota possui {$viagensCount} viagem(ns) vinculada(s).",
                    'status' => 'error',
                    '_links' => $this->hateoasService->generateCollectionLinks('rotas')
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Tentar excluir a rota
            $deleted = $this->service->deleteRota($id);
            
            $this->loggingService->logInfo('Rota excluída com sucesso', ['id' => $id]);
            return response()->json([
                'message' => 'Rota excluída com sucesso',
                'status' => 'success'
            ], Response::HTTP_OK);
            
        } catch (QueryException $e) {
            // Capturar erros específicos de banco de dados
            $errorCode = $e->errorInfo[1] ?? 0;
            $errorMessage = 'Erro ao excluir a rota';
            
            // Detalhar o erro com base no código específico do banco
            if ($errorCode == 1451) { // MySQL foreign key constraint error
                $errorMessage = 'Não é possível excluir esta rota porque ela está sendo usada em outras partes do sistema.';
            } elseif ($errorCode == 23503) { // PostgreSQL foreign key violation
                $errorMessage = 'Não é possível excluir esta rota porque ela está sendo usada em outras partes do sistema.';
            }
            
            $this->loggingService->logError('Erro de banco de dados ao excluir rota', [
                'id' => $id,
                'error_code' => $errorCode,
                'error_message' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => $errorMessage,
                'detalhes' => 'É necessário remover primeiro as viagens, paradas e outros itens relacionados.',
                'status' => 'error',
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_BAD_REQUEST);
            
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao excluir rota', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Ocorreu um erro ao excluir a rota',
                'detalhes' => $e->getMessage(),
                'status' => 'error',
                '_links' => $this->hateoasService->generateCollectionLinks('rotas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getParadas(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Buscando paradas da rota', ['id' => $id]);
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            $this->loggingService->logError('Rota não encontrada', ['id' => $id]);
            return response()->json([
                'message' => 'Rota não encontrada',
                'status' => 'error'
            ], Response::HTTP_NOT_FOUND);
        }

        $paradas = $this->service->getRotaParadas($id);
        return response()->json([
            'data' => $paradas,
            '_links' => $this->hateoasService->generateLinks('rotas', $id),
            'status' => 'success'
        ]);
    }

    public function getViagens(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Buscando viagens da rota', ['id' => $id]);
        $rota = $this->service->getRotaById($id);
        if (!$rota) {
            $this->loggingService->logError('Rota não encontrada', ['id' => $id]);
            return response()->json([
                'message' => 'Rota não encontrada',
                'status' => 'error'
            ], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->service->getRotaViagens($id);
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateLinks('rotas', $id),
            'status' => 'success'
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
                    $this->loggingService->logInfo("Campo de hora formatado {$field} de {$time} para {$hours}:{$matches[2]}");
                }
            }
        }
    }
}