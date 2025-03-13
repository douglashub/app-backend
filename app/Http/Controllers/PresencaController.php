<?php

namespace App\Http\Controllers;

use App\Services\PresencaService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class PresencaController extends Controller
{
    protected $presencaService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(PresencaService $presencaService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->presencaService = $presencaService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all presencas');
        $presencas = $this->presencaService->getAllPresencas();
        $response = [
            'data' => $presencas,
            '_links' => $this->hateoasService->generateCollectionLinks('presencas')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching presenca', ['id' => $id]);
        $presenca = $this->presencaService->getPresencaById($id);
        if (!$presenca) {
            $this->loggingService->logError('Presenca not found', ['id' => $id]);
            return response()->json(['message' => 'Presença não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $relationships = [
            'viagem' => $presenca->viagem_id,
            'aluno' => $presenca->aluno_id
        ];

        $response = [
            'data' => $presenca,
            '_links' => $this->hateoasService->generateLinks('presencas', $id, $relationships)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $this->loggingService->logInfo('Creating new presenca');
        $validatedData = $request->validate([
            'viagem_id' => 'required|integer|exists:viagens,id',
            'aluno_id' => 'required|integer|exists:alunos,id',
            'hora_registro' => 'required|date_format:H:i',
            'presente' => 'required|boolean'
        ]);

        $presenca = $this->presencaService->createPresenca($validatedData);
        $this->loggingService->logInfo('Presenca created', ['id' => $presenca->id]);
        $relationships = [
            'viagem' => $presenca->viagem_id,
            'aluno' => $presenca->aluno_id
        ];

        return response()->json([
            'data' => $presenca,
            '_links' => $this->hateoasService->generateLinks('presencas', $presenca->id, $relationships)
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->loggingService->logInfo('Updating presenca', ['id' => $id]);
        $validatedData = $request->validate([
            'viagem_id' => 'required|integer|exists:viagens,id',
            'aluno_id' => 'required|integer|exists:alunos,id',
            'hora_registro' => 'required|date_format:H:i',
            'presente' => 'required|boolean'
        ]);

        $presenca = $this->presencaService->updatePresenca($id, $validatedData);
        if (!$presenca) {
            $this->loggingService->logError('Presenca not found for update', ['id' => $id]);
            return response()->json(['message' => 'Presença não encontrada'], Response::HTTP_NOT_FOUND);
        }

        $relationships = [
            'viagem' => $presenca->viagem_id,
            'aluno' => $presenca->aluno_id
        ];

        return response()->json([
            'data' => $presenca,
            '_links' => $this->hateoasService->generateLinks('presencas', $id, $relationships)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Deleting presenca', ['id' => $id]);
        $deleted = $this->presencaService->deletePresenca($id);
        if (!$deleted) {
            $this->loggingService->logError('Presenca not found for deletion', ['id' => $id]);
            return response()->json(['message' => 'Presença não encontrada'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
