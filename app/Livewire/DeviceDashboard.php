<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\Device;

class DeviceDashboard extends Component
{
    public $clientsData = [];
    public $lastRefreshTime;

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        Log::info('DeviceDashboard mount called');
        $this->updateLastRefreshTime();
    }

    public function refreshData()
    {
        Log::info('DeviceDashboard refreshData called');
        try {
            Artisan::call('devices:fetch');
            Cache::forget('device_dashboard_clients');
            Log::info('Devices refreshed');
            $this->updateLastRefreshTime();
        } catch (\Exception $e) {
            Log::error('Error refreshing devices', ['error' => $e->getMessage()]);
            $this->lastRefreshTime = 'Error refreshing data';
        }
    }

    protected function updateLastRefreshTime()
    {
        $this->lastRefreshTime = Cache::get('devices_all_last_api_call', 'Not yet refreshed');
    }

    public function render()
    {
        Log::info('DeviceDashboard render called');
        \DB::enableQueryLog();
        $this->clientsData = Cache::remember('device_dashboard_clients', now()->addMinutes(5), function () {
            return Device::groupBy('client')
                ->selectRaw('client, COUNT(*) as total_devices, SUM(warning) as warning_count, SUM(error) as error_count')
                ->get()
                ->toArray();
        });
        $this->updateLastRefreshTime();
        Log::info('Queries executed', ['queries' => \DB::getQueryLog()]);
        \DB::disableQueryLog();
        return view('livewire.device-dashboard')
            ->layout('layouts.app');
    }
}