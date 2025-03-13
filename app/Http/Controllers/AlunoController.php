<?php

namespace App\Http\Controllers;

use App\Services\AlunoService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AlunoController extends Controller
{
    protected $alunoService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(AlunoService $alunoService, HateoasService $hateoasService, LoggingService $loggingService)
    {
        $this->alunoService = $alunoService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    public function index(): JsonResponse
    {
        $this->loggingService->logInfo('Fetching all alunos');
        $alunos = $this->alunoService->getAllAlunos();
        $response = [
            'data' => $alunos,
            '_links' => $this->hateoasService->generateCollectionLinks('alunos')
        ];
        return response()->json($response);
    }

    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching aluno', ['id' => $id]);
        $aluno = $this->alunoService->getAlunoById($id);
        if (!$aluno) {
            $this->loggingService->logError('Aluno not found', ['id' => $id]);
            return response()->json(['message' => 'Aluno não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'data' => $aluno,
            '_links' => $this->hateoasService->generateLinks('alunos', $id)
        ];

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Creating new aluno');
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

            $aluno = $this->alunoService->createAluno($validatedData);
            $this->loggingService->logInfo('Aluno created', ['id' => $aluno->id]);

            return response()->json([
                'data' => $aluno,
                '_links' => $this->hateoasService->generateLinks('alunos', $aluno->id)
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('alunos')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Updating aluno', ['id' => $id]);
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

            $aluno = $this->alunoService->updateAluno($id, $validatedData);
            if (!$aluno) {
                $this->loggingService->logError('Aluno update failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Aluno não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('alunos')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Aluno updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $aluno,
                '_links' => $this->hateoasService->generateLinks('alunos', $id)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('alunos')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Deleting aluno', ['id' => $id]);
            $deleted = $this->alunoService->deleteAluno($id);
            if (!$deleted) {
                $this->loggingService->logError('Aluno deletion failed', ['id' => $id]);
                return response()->json([
                    'message' => 'Aluno não encontrado',
                    '_links' => $this->hateoasService->generateCollectionLinks('alunos')
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Aluno deleted successfully', ['id' => $id]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                '_links' => $this->hateoasService->generateCollectionLinks('alunos')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function presencas(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching aluno presencas', ['id' => $id]);
        $aluno = $this->alunoService->getAlunoById($id);
        if (!$aluno) {
            $this->loggingService->logError('Aluno not found', ['id' => $id]);
            return response()->json(['message' => 'Aluno não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $presencas = $this->alunoService->getAlunoPresencas($id);
        return response()->json([
            'data' => $presencas,
            '_links' => $this->hateoasService->generateLinks('alunos', $id)
        ]);
    }
}