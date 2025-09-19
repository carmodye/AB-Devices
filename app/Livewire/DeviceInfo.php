<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\Client;
use App\Models\Device;

class DeviceInfo extends Component
{
    use WithPagination;

    public $client = '';
    public $clients = [];
    public $error = '';
    public $perPage = 50;
    public $lastApiCall = null;
    public $sortField = 'last_status';
    public $sortDirection = 'desc';
    public $macSearch = '';

    protected $paginationTheme = 'tailwind';
    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        Log::info('Mount called', ['client' => $this->client]);
        $this->clients = Client::pluck('name')->toArray();
        $this->client = !empty($this->clients) ? $this->clients[0] : '';
        if ($this->client) {
            $this->loadLastApiCall();
        }
    }

    public function updatedClient($value)
    {
        Log::info('updatedClient called', ['client' => $value, 'previous_client' => $this->client]);
        $this->client = $value;
        $this->macSearch = '';
        $this->resetPage();
        if ($this->client) {
            $this->loadLastApiCall();
        } else {
            $this->error = 'Please select a client';
            $this->lastApiCall = null;
        }
    }

    public function updatedMacSearch()
    {
        Log::info('MAC search updated', ['macSearch' => $this->macSearch]);
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        Log::info('Sorting updated', ['sortField' => $this->sortField, 'sortDirection' => $this->sortDirection]);
        $this->resetPage();
    }

    public function refreshDevices()
    {
        Log::info('refreshDevices called', ['client' => $this->client]);
        if ($this->client) {
            Cache::forget('devices_' . $this->client . '_last_api_call');
            Cache::forget('device_info_' . $this->client);
            Artisan::call('devices:fetch', ['--client' => $this->client]);
            $this->loadLastApiCall();
            $this->dispatch('refresh');
        }
    }

    public function loadLastApiCall()
    {
        $cacheKey = 'devices_' . $this->client . '_last_api_call';
        $this->lastApiCall = Cache::get($cacheKey, 'Not yet refreshed');
        Log::info('Last API call loaded', ['client' => $this->client, 'last_api_call' => $this->lastApiCall]);
    }

    public function render()
    {
        Log::info('Render called', [
            'client' => $this->client,
            'page' => request()->query('page', 1),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'macSearch' => $this->macSearch
        ]);

        $query = Device::where('client', $this->client);

        if ($this->macSearch) {
            $query->where('macAddress', 'like', '%' . $this->macSearch . '%');
        }

        if ($this->sortField === 'last_status') {
            $query->orderBy('unixepoch', $this->sortDirection);
        }

        $paginatedDevices = Cache::remember('device_info_' . $this->client . '_page_' . request()->query('page', 1), now()->addMinutes(5), function () use ($query) {
            return $query->paginate($this->perPage);
        });

        if ($paginatedDevices->isEmpty() && $this->client) {
            $this->error = 'No devices found for client: ' . $this->client;
        } else {
            $this->error = '';
        }

        return view('livewire.device-info', [
            'paginatedDevices' => $paginatedDevices,
            'totalDevices' => $paginatedDevices->total(),
            'lastApiCall' => $this->lastApiCall
        ])->layout('layouts.app');
    }
}