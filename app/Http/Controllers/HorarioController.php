<?php

namespace App\Http\Controllers;

use App\Services\HorarioService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HorarioController extends Controller
{
    protected $horarioService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(HorarioService $horarioService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->horarioService = $horarioService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all horarios');
        $horarios = $this->horarioService->getAllHorarios();
        $response = [
            'data' => $horarios,
            '_links' => $this->hateoasService->generateCollectionLinks('horarios')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching horario', ['id' => $id]);
        $horario = $this->horarioService->getHorarioById($id);
        if (!$horario) {
            $this->loggingService->logError('Horario not found', ['id' => $id]);
            return response()->json(['message' => 'Horario não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $horario,
            '_links' => $this->hateoasService->generateLinks('horarios', $id)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new horario');

            $validatedData = $request->validate([
                'rota_id' => 'required|integer|exists:rotas,id',
                'nome' => 'required|string|max:255',       // <-- Adicionado
                'descricao' => 'nullable|string|max:255',  // <-- Adicionado
                'dias_semana' => 'required|array',
                'tipo' => 'required|in:REGULAR,ESPECIAL',
                'dias_semana.*' => 'integer|min:0|max:6',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
                'status' => 'required|boolean'
            ]);

            $horario = $this->horarioService->createHorario($validatedData);

            $this->loggingService->logInfo('Horario created', ['id' => $horario->id]);

            return response()->json([
                'data' => $horario,
                '_links' => $this->hateoasService->generateLinks('horarios', $horario->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('horarios')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('horarios')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating horario', ['id' => $id]);
            $validatedData = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'descricao' => 'sometimes|string|max:255',
                'dias_semana' => 'sometimes|array',
                'dias_semana.*' => 'integer|min:0|max:6',
                'hora_inicio' => 'sometimes|date_format:H:i',
                'hora_fim' => 'sometimes|date_format:H:i|after:hora_inicio',
                'status' => 'sometimes|boolean',
                'tipo' => 'sometimes|in:REGULAR,ESPECIAL'
            ]);            

            $horario = $this->horarioService->updateHorario($id, $validatedData);
            if (!$horario) {
                $this->loggingService->logError('Horario update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Horário não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('horarios')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Horario updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $horario,
                '_links' => $this->hateoasService->generateLinks('horarios', $id)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('horarios')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('horarios')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting horario', ['id' => $id]);
            $deleted = $this->horarioService->deleteHorario($id);
            if (!$deleted) {
                $this->loggingService->logError('Horario deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Horário não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('horarios')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Horario deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('horarios')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function viagens(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching horario viagens', ['id' => $id]);
        $horario = $this->horarioService->getHorarioById($id);
        if (!$horario) {
            $this->loggingService->logError('Horario not found', ['id' => $id]);
            return response()->json(['message' => 'Horário não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->horarioService->getHorarioViagens($id);
        return response()->json([
            'data' => $viagens,
            '_links' => $this->hateoasService->generateLinks('horarios', $id)
        ]);
    }
}
