<?php

namespace App\Http\Controllers;

use App\Services\MonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class MonitorController extends Controller
{
    protected $monitorService;

    public function __construct(MonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

 
    public function index(): JsonResponse
    {
        $monitores = $this->monitorService->getAllMonitores();
        return response()->json($monitores);
    }

 
    public function show(int $id): JsonResponse
    {
        $monitor = $this->monitorService->getMonitorById($id);
        if (!$monitor) {
            return response()->json(['message' => 'Monitor não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($monitor);
    }

  
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_nascimento' => 'required|date',
            'responsavel' => 'required|string|max:255',
            'telefone_responsavel' => 'required|string|max:20',
            'endereco' => 'required|string',
            'ponto_referencia' => 'nullable|string',
            'status' => 'required|boolean'
        ]);

        $monitor = $this->monitorService->createMonitor($validatedData);
        return response()->json($monitor, Response::HTTP_CREATED);
    }


    public function update(Request $request, int $id): JsonResponse
    {
        $validatedData = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'descricao' => 'nullable|string',
            'data_nascimento' => 'sometimes|date',
            'responsavel' => 'sometimes|string|max:255',
            'telefone_responsavel' => 'sometimes|string|max:20',
            'endereco' => 'sometimes|string',
            'ponto_referencia' => 'nullable|string',
            'status' => 'sometimes|boolean'
        ]);

        $monitor = $this->monitorService->updateMonitor($id, $validatedData);
        if (!$monitor) {
            return response()->json(['message' => 'Monitor não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($monitor);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->monitorService->deleteMonitor($id);
        if (!$deleted) {
            return response()->json(['message' => 'Monitor não encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
