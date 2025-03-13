<?php

namespace App\Http\Controllers;

use App\Services\HorarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HorarioController extends Controller
{
    protected $horarioService;

    public function __construct(HorarioService $horarioService)
    {
        $this->horarioService = $horarioService;
    }


    public function index(): JsonResponse
    {
        $horarios = $this->horarioService->getAllHorarios();
        return response()->json($horarios);
    }

    public function show(int $id): JsonResponse
    {
        $horario = $this->horarioService->getHorarioById($id);
        if (!$horario) {
            return response()->json(['message' => 'Horario não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($horario);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'dia_semana' => 'required|integer|min:0|max:6',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'status' => 'required|boolean'
        ]);

        $horario = $this->horarioService->createHorario($validatedData);
        return response()->json($horario, Response::HTTP_CREATED);
    }


    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'dia_semana' => 'sometimes|integer|min:0|max:6',
            'hora_inicio' => 'sometimes|date_format:H:i',
            'hora_fim' => 'sometimes|date_format:H:i|after:hora_inicio',
            'status' => 'sometimes|boolean'
        ]);

        $horario = $this->horarioService->updateHorario($id, $validatedData);
        if (!$horario) {
            return response()->json(['message' => 'Horário não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($horario);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->horarioService->deleteHorario($id);
        if (!$deleted) {
            return response()->json(['message' => 'Horário não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function viagens(int $id): JsonResponse
    {
        $horario = $this->horarioService->getHorarioById($id);
        if (!$horario) {
            return response()->json(['message' => 'Horário não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $viagens = $this->horarioService->getHorarioViagens($id);
        return response()->json($viagens);
    }
}
