<?php

namespace App\Services;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Collection;

class MonitorService
{
    public function getAllMonitores(): Collection
    {
        return Monitor::all();
    }

    public function getMonitorById(int $id): ?Monitor
    {
        return Monitor::find($id);
    }

    public function createMonitor(array $data): Monitor
    {
        return Monitor::create($data);
    }

    public function updateMonitor(int $id, array $data): ?Monitor
    {
        $monitor = $this->getMonitorById($id);
        if (!$monitor) {
            return null;
        }

        $monitor->update($data);
        return $monitor->fresh();
    }

    public function deleteMonitor(int $id): bool
    {
        $monitor = $this->getMonitorById($id);
        if (!$monitor) {
            return false;
        }

        return $monitor->delete();
    }

    public function getMonitorViagens(int $id): Collection
    {
        $monitor = $this->getMonitorById($id);
        if (!$monitor) {
            return collect([]);
        }

        return $monitor->viagens;
    }
}