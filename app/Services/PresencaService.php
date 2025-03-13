<?php

namespace App\Services;

use App\Models\Presenca;
use App\Models\Aluno;
use App\Models\Viagem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresencaService
{
    /**
     * Retrieve all presencas with related models
     */
    public function getAllPresencas(): Collection
    {
        try {
            return Presenca::with(['aluno', 'viagem'])->get();
        } catch (\Exception $e) {
            Log::channel('application')->error('Error retrieving all presencas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Find a specific presenca by ID with related models
     */
    public function getPresencaById(int $id): ?Presenca
    {
        try {
            return Presenca::with(['aluno', 'viagem'])->find($id);
        } catch (\Exception $e) {
            Log::channel('application')->error('Error finding presenca', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new presenca with validation and transaction
     */
    public function createPresenca(array $data): Presenca
    {
        return DB::transaction(function () use ($data) {
            try {
                // Validate relationships
                $viagem = Viagem::findOrFail($data['viagem_id']);
                $aluno = Aluno::findOrFail($data['aluno_id']);

                // Prepare data for creation
                $presencaData = [
                    'viagem_id' => $data['viagem_id'],
                    'aluno_id' => $data['aluno_id'],
                    'hora_embarque' => $data['hora_embarque'],
                    'presente' => $data['presente'],
                    'observacoes' => $data['observacoes'] ?? null
                ];

                // Create presenca
                $presenca = Presenca::create($presencaData);

                // Log successful creation
                Log::channel('application')->info('Presenca created successfully', [
                    'id' => $presenca->id,
                    'viagem_id' => $presenca->viagem_id,
                    'aluno_id' => $presenca->aluno_id
                ]);

                return $presenca->refresh();

            } catch (\Exception $e) {
                // Log creation error
                Log::channel('application')->error('Error creating presenca', [
                    'data' => $data,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Update an existing presenca
     */
    public function updatePresenca(int $id, array $data): ?Presenca
    {
        return DB::transaction(function () use ($id, $data) {
            try {
                // Find the existing presenca
                $presenca = $this->getPresencaById($id);
                
                if (!$presenca) {
                    Log::channel('application')->warning('Presenca not found for update', [
                        'id' => $id
                    ]);
                    return null;
                }

                // Validate relationships if provided
                if (isset($data['viagem_id'])) {
                    Viagem::findOrFail($data['viagem_id']);
                }

                if (isset($data['aluno_id'])) {
                    Aluno::findOrFail($data['aluno_id']);
                }

                // Update the presenca
                $presenca->update($data);

                // Refresh the model to get updated data
                $updatedPresenca = $presenca->refresh();

                // Log successful update
                Log::channel('application')->info('Presenca updated successfully', [
                    'id' => $id,
                    'updated_data' => $data
                ]);

                return $updatedPresenca;

            } catch (\Exception $e) {
                // Log update error
                Log::channel('application')->error('Error updating presenca', [
                    'id' => $id,
                    'data' => $data,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Delete a presenca
     */
    public function deletePresenca(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            try {
                // Find the presenca
                $presenca = $this->getPresencaById($id);
                
                if (!$presenca) {
                    Log::channel('application')->warning('Presenca not found for deletion', [
                        'id' => $id
                    ]);
                    return false;
                }

                // Delete the presenca
                $deleted = $presenca->delete();

                // Log successful deletion
                Log::channel('application')->info('Presenca deleted successfully', [
                    'id' => $id
                ]);

                return $deleted;

            } catch (\Exception $e) {
                // Log deletion error
                Log::channel('application')->error('Error deleting presenca', [
                    'id' => $id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get presencas for a specific viagem
     */
    public function getPresencasByViagem(int $viagemId): Collection
    {
        try {
            // Verify viagem exists
            Viagem::findOrFail($viagemId);

            return Presenca::where('viagem_id', $viagemId)
                ->with(['aluno', 'viagem'])
                ->get();
        } catch (\Exception $e) {
            Log::channel('application')->error('Error retrieving presencas by viagem', [
                'viagem_id' => $viagemId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get presencas for a specific aluno
     */
    public function getPresencasByAluno(int $alunoId): Collection
    {
        try {
            // Verify aluno exists
            Aluno::findOrFail($alunoId);

            return Presenca::where('aluno_id', $alunoId)
                ->with(['aluno', 'viagem'])
                ->get();
        } catch (\Exception $e) {
            Log::channel('application')->error('Error retrieving presencas by aluno', [
                'aluno_id' => $alunoId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Count presencas for a specific aluno
     */
    public function countPresencasByAluno(int $alunoId): int
    {
        try {
            // Verify aluno exists
            Aluno::findOrFail($alunoId);

            return Presenca::where('aluno_id', $alunoId)
                ->where('presente', true)
                ->count();
        } catch (\Exception $e) {
            Log::channel('application')->error('Error counting presencas by aluno', [
                'aluno_id' => $alunoId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}