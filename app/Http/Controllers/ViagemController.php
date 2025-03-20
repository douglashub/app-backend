<?php

namespace App\Http\Controllers;

use App\Services\ViagemService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Horario;

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
        $viagens = $this->service->getAllViagens()->load(['rota', 'onibus', 'motorista', 'monitor', 'horario']);
        
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateCollectionLinks('viagens')
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Incoming Viagem Creation Request', [
                'request_data' => $request->all()
            ]);

            // Formatar horários antes da validação
            $this->formatTimeFields($request);

            $validatedData = $request->validate([
                'data_viagem' => 'required|date',
                'rota_id' => 'required|integer|exists:rotas,id',
                'onibus_id' => 'required|integer|exists:onibus,id',
                'motorista_id' => 'required|integer|exists:motoristas,id',
                'monitor_id' => 'nullable|integer|exists:monitores,id',
                'horario_id' => 'nullable|integer', // Não checa existência aqui

                'hora_saida_prevista' => 'required|date_format:H:i',
                'hora_chegada_prevista' => 'nullable|date_format:H:i|after:hora_saida_prevista',
                'hora_saida_real' => 'nullable|date_format:H:i',
                'hora_chegada_real' => 'nullable|date_format:H:i',
                'status' => 'required|boolean',
                'observacoes' => 'nullable|string'
            ]);

            // Validação de `horario_id`, mas **somente se um valor for enviado**
            if (!empty($validatedData['horario_id']) && !Horario::where('id', $validatedData['horario_id'])->exists()) {
                return response()->json([
                    'message' => 'Erro de validação',
                    'errors' => ['horario_id' => 'O horário selecionado não existe.'],
                    '_links' => $this->hateoasService->generateCollectionLinks('viagens')
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $viagem = $this->service->createViagem($validatedData);
            $this->loggingService->logInfo('Viagem created', ['id' => $viagem->id]);

            return response()->json([
                'data' => $viagem,
                '_links' => $this->hateoasService->generateLinks('viagens', $viagem->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Erro no servidor',
                '_links' => $this->hateoasService->generateCollectionLinks('viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching viagem', ['id' => $id]);
        $viagem = $this->service->getViagemById($id);

        if (!$viagem) {
            return response()->json(['message' => 'Viagem não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $viagem,
            '_links' => $this->hateoasService->generateLinks('viagens', $id)
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating viagem', ['id' => $id]);

            $this->formatTimeFields($request);

            $validatedData = $request->validate([
                'data_viagem' => 'sometimes|date',
                'rota_id' => 'sometimes|integer|exists:rotas,id',
                'onibus_id' => 'sometimes|integer|exists:onibus,id',
                'motorista_id' => 'sometimes|integer|exists:motoristas,id',
                'monitor_id' => 'nullable|integer|exists:monitores,id',
                'horario_id' => 'sometimes|integer',

                'hora_saida_prevista' => 'nullable|date_format:H:i',
                'hora_chegada_prevista' => 'nullable|date_format:H:i',
                'hora_saida_real' => 'nullable|date_format:H:i',
                'hora_chegada_real' => 'nullable|date_format:H:i',

                'status' => 'sometimes|boolean',
                'observacoes' => 'nullable|string'
            ]);

            if (!empty($validatedData['horario_id']) && !Horario::where('id', $validatedData['horario_id'])->exists()) {
                return response()->json([
                    'message' => 'Erro de validação',
                    'errors' => ['horario_id' => 'O horário selecionado não existe.'],
                    '_links' => $this->hateoasService->generateCollectionLinks('viagens')
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $viagem = $this->service->updateViagem($id, $validatedData);

            if (!$viagem) {
                return response()->json(['message' => 'Viagem não encontrada'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => $viagem,
                '_links' => $this->hateoasService->generateLinks('viagens', $id)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro no servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteViagem($id);
        if (!$deleted) {
            return response()->json(['message' => 'Viagem não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function formatTimeFields(Request $request): void
    {
        try {
            $timeFields = ['hora_saida_prevista', 'hora_chegada_prevista', 'hora_saida_real', 'hora_chegada_real'];

            foreach ($timeFields as $field) {
                if ($request->has($field) && $request->input($field)) {
                    $time = $request->input($field);
                    if (preg_match('/^(\d{1}):(\d{2})$/', $time, $matches)) {
                        $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $request->merge([$field => "{$hours}:{$matches[2]}"]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->loggingService->logError("Error formatting time field", ['message' => $e->getMessage()]);
        }
    }
}
