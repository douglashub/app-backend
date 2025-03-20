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
            // Log the entire incoming request data for debugging
            $this->loggingService->logInfo('Incoming Viagem Creation Request', [
                'all_data' => $request->all(),
                'input_data' => $request->input()
            ]);

            // Formatar campos de hora antes da validação
            $this->formatTimeFields($request);

            // Additional logging of formatted data
            $this->loggingService->logInfo('Formatted Request Data', [
                'formatted_data' => $request->all()
            ]);

            $validatedData = $request->validate([
                'data_viagem' => 'required|date',
                'rota_id' => 'required|integer|exists:rotas,id',
                'onibus_id' => 'required|integer|exists:onibus,id',
                'motorista_id' => 'required|integer|exists:motoristas,id',
                'monitor_id' => 'nullable|integer|exists:monitores,id',
                'horario_id' => 'nullable|integer|exists:horarios,id',
                'hora_saida_prevista' => 'required|date_format:H:i',
                'hora_chegada_prevista' => 'nullable|date_format:H:i|after:hora_saida_prevista',
                'hora_saida_real' => 'nullable|date_format:H:i',
                'hora_chegada_real' => 'nullable|date_format:H:i',
                'status' => 'required|boolean',
                'observacoes' => 'nullable|string'
            ]);

            // Additional logging of validated data
            $this->loggingService->logInfo('Validated Viagem Data', [
                'validated_data' => $validatedData
            ]);

            $viagem = $this->service->createViagem($validatedData);
            $this->loggingService->logInfo('Viagem created', ['id' => $viagem->id]);

            $relationships = [
                'rota' => $viagem->rota_id,
                'onibus' => $viagem->onibus_id,
                'motorista' => $viagem->motorista_id,
                'monitor' => $viagem->monitor_id,
                'horario' => $viagem->horario_id
            ];

            return response()->json([
                'data' => $viagem,
                '_links' => $this->hateoasService->generateLinks('viagens', $viagem->id, $relationships)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // More detailed validation error logging
            $this->loggingService->logError('Validation failed', [
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                'request_data' => $request->all(), // Include request data for debugging
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            // More comprehensive error logging
            $this->loggingService->logError('Server error during viagem creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Erro no servidor',
                'error_details' => $e->getMessage(),
                'request_data' => $request->all(), // Include request data for debugging
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
            'monitores' => $viagem->monitor_id,
            'horarios' => $viagem->horario_id
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
            $this->loggingService->logInfo('Updating viagem', ['id' => $id]);

            // Formatar campos de hora antes da validação
            $this->formatTimeFields($request);

            $validatedData = $request->validate([
                'data_viagem' => 'sometimes|date',
                'rota_id' => 'sometimes|integer|exists:rotas,id',
                'onibus_id' => 'sometimes|integer|exists:onibus,id',
                'motorista_id' => 'sometimes|integer|exists:motoristas,id',
                'monitor_id' => 'nullable|integer|exists:monitores,id',
                'horario_id' => 'sometimes|integer|exists:horarios,id',

                // Modificar validação de campos de hora
                'hora_saida_prevista' => 'nullable|date_format:H:i',
                'hora_chegada_prevista' => 'nullable|date_format:H:i',
                'hora_saida_real' => 'nullable|date_format:H:i',
                'hora_chegada_real' => 'nullable|date_format:H:i',

                'status' => 'sometimes|boolean',
                'observacoes' => 'nullable|string'
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

            $relationships = [
                'rotas' => $viagem->rota_id,
                'onibus' => $viagem->onibus_id,
                'motoristas' => $viagem->motorista_id,
                'monitores' => $viagem->monitor_id,
                'horarios' => $viagem->horario_id
            ];

            return response()->json([
                'data' => $viagem,
                '_links' => $this->hateoasService->generateLinks('viagens', $id, $relationships)
            ]);
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
            $this->loggingService->logInfo('Deleting viagem', ['id' => $id]);
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

    /**
     * Formata os campos de hora para garantir que estejam no padrão H:i
     * 
     * @param Request $request
     * @return void
     */
    private function formatTimeFields(Request $request): void
    {
        $timeFields = [
            'hora_saida_prevista',
            'hora_chegada_prevista',
            'hora_saida_real',
            'hora_chegada_real'
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
