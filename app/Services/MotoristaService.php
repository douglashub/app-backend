<?php

namespace App\Services;

use App\Models\Motorista;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class MotoristaService
{
    /**
     * Get all motoristas
     *
     * @return Collection
     */
    public function getAllMotoristas(): Collection
    {
        try {
            return Motorista::all();
        } catch (\Exception $e) {
            Log::channel('application')->error('Error fetching all motoristas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Return empty collection instead of throwing
            return collect([]);
        }
    }

    /**
     * Get motorista by ID
     *
     * @param int $id
     * @return Motorista|null
     */
    public function getMotoristaById(int $id): ?Motorista
    {
        try {
            return Motorista::find($id);
        } catch (\Exception $e) {
            Log::channel('application')->error('Error finding motorista: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create a new motorista with error handling
     *
     * @param array $data
     * @return Motorista
     */
    public function createMotorista(array $data): Motorista
    {
        try {
            // Log data for debugging
            Log::channel('application')->info('Creating motorista with data', ['data' => $data]);
            
            // Ensure the status is in one of the allowed values
            if (isset($data['status'])) {
                $status = $data['status'];
                $allowedStatuses = ['Ativo', 'Inativo', 'Ferias', 'Licenca'];
                
                // Handle various status formats and normalize them
                if (is_bool($status)) {
                    $data['status'] = $status ? 'Ativo' : 'Inativo';
                } elseif (is_numeric($status)) {
                    $data['status'] = $status ? 'Ativo' : 'Inativo';
                } elseif (is_string($status) && !in_array($status, $allowedStatuses)) {
                    // Default to 'Ativo' if not in allowed list
                    $data['status'] = 'Ativo';
                }
            } else {
                // Default value if status is not set
                $data['status'] = 'Ativo';
            }
            
            // Create the motorista
            $motorista = Motorista::create($data);
            
            Log::channel('application')->info('Motorista created successfully', [
                'id' => $motorista->id
            ]);
            
            return $motorista;
        } catch (\Exception $e) {
            Log::channel('application')->error('Failed to create motorista: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing motorista with error handling
     *
     * @param int $id
     * @param array $data
     * @return Motorista|null
     */
    public function updateMotorista(int $id, array $data): ?Motorista
    {
        try {
            // Log data for debugging
            Log::channel('application')->info('Updating motorista with data', [
                'id' => $id,
                'data' => $data
            ]);
            
            $motorista = $this->getMotoristaById($id);
            if (!$motorista) {
                Log::channel('application')->warning('Motorista not found for update', ['id' => $id]);
                return null;
            }

            // Ensure the status is in one of the allowed values
            if (isset($data['status'])) {
                $status = $data['status'];
                $allowedStatuses = ['Ativo', 'Inativo', 'Ferias', 'Licenca'];
                
                // Handle various status formats and normalize them
                if (is_bool($status)) {
                    $data['status'] = $status ? 'Ativo' : 'Inativo';
                } elseif (is_numeric($status)) {
                    $data['status'] = $status ? 'Ativo' : 'Inativo';
                } elseif (is_string($status) && !in_array($status, $allowedStatuses)) {
                    // Use existing status if new one is not in allowed list
                    $data['status'] = $motorista->status;
                }
            }
            
            $motorista->update($data);
            
            Log::channel('application')->info('Motorista updated successfully', [
                'id' => $id
            ]);
            
            return $motorista->fresh();
        } catch (\Exception $e) {
            Log::channel('application')->error('Failed to update motorista: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a motorista with error handling
     *
     * @param int $id
     * @return bool
     */
    public function deleteMotorista(int $id): bool
    {
        try {
            $motorista = $this->getMotoristaById($id);
            if (!$motorista) {
                Log::channel('application')->warning('Motorista not found for deletion', ['id' => $id]);
                return false;
            }

            $result = $motorista->delete();
            
            Log::channel('application')->info('Motorista deleted successfully', [
                'id' => $id,
                'result' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::channel('application')->error('Failed to delete motorista: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get all viagens for a motorista
     *
     * @param int $id
     * @return Collection
     */
    public function getMotoristaViagens(int $id): Collection
    {
        try {
            $motorista = $this->getMotoristaById($id);
            if (!$motorista) {
                return collect([]);
            }

            return $motorista->viagens;
        } catch (\Exception $e) {
            Log::channel('application')->error('Error fetching motorista viagens: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }
}