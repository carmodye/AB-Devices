<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Device;

class DeviceDashboard extends Component
{
    public $clientsData = [];

    public function mount()
    {
        Log::info('DeviceDashboard mount called');
    }

    public function render()
    {
        Log::info('DeviceDashboard render called');

        // Enable query logging for debugging
        \DB::enableQueryLog();

        // Cache the query results for 5 minutes
        $this->clientsData = Cache::remember('device_dashboard_clients', now()->addMinutes(5), function () {
            return Device::groupBy('client')
                ->selectRaw('client, COUNT(*) as total_devices, SUM(warning) as warning_count, SUM(error) as error_count')
                ->get()
                ->toArray();
        });

        // Log queries executed
        Log::info('Queries executed', ['queries' => \DB::getQueryLog()]);
        \DB::disableQueryLog();

        return view('livewire.device-dashboard')
            ->layout('layouts.app');
    }
}