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
        try {
            $alunos = $this->alunoService->getAllAlunos();
            
            // Verificação explícita para garantir que temos um objeto de paginação válido
            if (!$alunos) {
                throw new \Exception("Falha ao recuperar os dados de alunos");
            }
            
            // Assegurar que items() retorna um array, mesmo que vazio
            $data = method_exists($alunos, 'items') ? $alunos->items() : [];
            
            $response = [
                'data' => $data,
                'meta' => [
                    'current_page' => method_exists($alunos, 'currentPage') ? $alunos->currentPage() : 1,
                    'per_page' => method_exists($alunos, 'perPage') ? $alunos->perPage() : count($data),
                    'total' => method_exists($alunos, 'total') ? $alunos->total() : count($data),
                    'last_page' => method_exists($alunos, 'lastPage') ? $alunos->lastPage() : 1
                ],
                '_links' => $this->hateoasService->generateCollectionLinks('alunos', $alunos)
            ];
            
            return response()->json($response);
        } catch (\Exception $e) {
            $this->loggingService->logError('Error fetching alunos: ' . $e->getMessage());
            
            // Em caso de erro, ainda retorna uma estrutura JSON válida
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1
                ],
                '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                'error' => 'Erro ao recuperar alunos: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching aluno', ['id' => $id]);
        $aluno = $this->alunoService->getAlunoById($id);
        if (!$aluno) {
            $this->loggingService->logError('Aluno not found', ['id' => $id]);
            return response()->json([
                'message' => 'Aluno não encontrado',
                'status' => 'error'
            ], Response::HTTP_NOT_FOUND);
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
                '_links' => $this->hateoasService->generateLinks('alunos', $aluno->id),
                'message' => 'Aluno criado com sucesso',
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                'status' => 'error'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro no servidor: ' . $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                'status' => 'error'
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
                    '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                    'status' => 'error'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Aluno updated successfully', ['id' => $id]);
            return response()->json([
                'data' => $aluno,
                '_links' => $this->hateoasService->generateLinks('alunos', $id),
                'message' => 'Aluno atualizado com sucesso',
                'status' => 'success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loggingService->logError('Validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                'status' => 'error'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            $this->loggingService->logError('Server error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro no servidor: ' . $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                'status' => 'error'
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
                    '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                    'status' => 'error'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->loggingService->logInfo('Aluno deleted successfully', ['id' => $id]);
            return response()->json([
                'message' => 'Aluno excluído com sucesso',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->loggingService->logError('Deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao excluir: ' . $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('alunos'),
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function presencas(int $id): JsonResponse
    {
        $this->loggingService->logInfo('Fetching aluno presencas', ['id' => $id]);
        $aluno = $this->alunoService->getAlunoById($id);
        if (!$aluno) {
            $this->loggingService->logError('Aluno not found', ['id' => $id]);
            return response()->json([
                'message' => 'Aluno não encontrado', 
                'status' => 'error'
            ], Response::HTTP_NOT_FOUND);
        }

        $presencas = $this->alunoService->getAlunoPresencas($id);
        return response()->json([
            'data' => $presencas,
            '_links' => $this->hateoasService->generateLinks('alunos', $id),
            'status' => 'success'
        ]);
    }
}