<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DeviceInfo extends Component
{
    use WithPagination;

    public $client = '';
    public $clients = [];
    public $devices;
    public $error = '';
    public $perPage = 50;
    public $timezone = 'America/New_York';
    public $lastApiCall = null;
    public $sortField = 'last_status'; // Default sort by Last Status
    public $sortDirection = 'desc'; // Default descending
    public $macSearch = ''; // MAC address search

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        Log::info('Mount called', ['client' => $this->client]);
        $this->clients = Client::pluck('name')->toArray();
        $this->devices = collect([]);
        $this->client = !empty($this->clients) ? $this->clients[0] : '';
        if ($this->client) {
            Log::info('Loading cached devices on mount', ['client' => $this->client]);
            $this->loadCachedDevices();
            $this->loadLastApiCall();
        }
    }

    public function updatedClient($value)
    {
        Log::info('updatedClient called', ['client' => $value, 'previous_client' => $this->client]);
        $this->client = $value;
        $this->macSearch = ''; // Reset MAC search on client change
        $this->resetPage();
        if ($this->client) {
            $this->loadCachedDevices();
            $this->loadLastApiCall();
        } else {
            $this->devices = collect([]);
            $this->error = 'Please select a client';
            $this->lastApiCall = null;
        }
    }

    public function updatedTimezone($value)
    {
        Log::info('Timezone updated', ['timezone' => $value]);
        $this->loadLastApiCall();
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
            Cache::forget('devices_' . $this->client);
            Cache::forget('devices_' . $this->client . '_last_api_call');
            $this->fetchDevices();
            $this->loadLastApiCall();
            $this->emit('refresh');
        }
    }

    public function loadCachedDevices()
    {
        Log::info('loadCachedDevices called', ['client' => $this->client]);
        $this->devices = collect([]);
        $this->error = '';

        if (empty($this->client)) {
            $this->error = 'Please select a client';
            Log::warning('No client selected');
            return;
        }

        $cacheKey = 'devices_' . $this->client;
        $cachedDevices = Cache::get($cacheKey);
        if ($cachedDevices) {
            $this->devices = $cachedDevices;
            Log::info('Loaded cached devices', ['client' => $this->client, 'count' => $this->devices->count()]);
        } else {
            $this->error = 'No cached data available for client: ' . $this->client;
            Log::warning('No cached data found', ['client' => $this->client]);
        }

        if ($this->devices->isEmpty()) {
            $this->error = 'No devices found for client: ' . $this->client;
        }
    }

    public function fetchDevices()
    {
        Log::info('fetchDevices called', ['client' => $this->client]);
        $this->devices = collect([]);
        $this->error = '';

        if (empty($this->client)) {
            $this->error = 'Please select a client';
            Log::warning('No client selected');
            return;
        }

        try {
            $cacheKey = 'devices_' . $this->client;
            $cacheTTL = now()->addMinutes(10);

            $this->devices = Cache::remember($cacheKey, $cacheTTL, function () use ($cacheKey) {
                $url = env('DEVICE_API_URL');
                if (!$url) {
                    Log::error('DEVICE_API_URL not set');
                    throw new \Exception('API URL not configured');
                }
                $response = Http::timeout(10)->get($url, [
                    'client' => $this->client
                ]);
                Log::info('API Response', [
                    'url' => $url . '?client=' . $this->client,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    $result = collect(is_array($data) ? $data : []);
                    Cache::put($cacheKey . '_last_api_call', now()->toDateTimeString(), $cacheTTL);
                    return $result;
                }
                Log::error('API request failed', ['status' => $response->status()]);
                throw new \Exception('API request failed with status: ' . $response->status());
            });

            if ($this->devices->isEmpty()) {
                $this->error = 'No devices found for client: ' . $this->client;
                Log::warning('No devices found', ['client' => $this->client]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error fetching devices: ' . $e->getMessage();
            $this->devices = collect([]);
            $this->lastApiCall = null;
            Log::error('API Request Exception', [
                'error' => $e->getMessage(),
                'client' => $this->client
            ]);
        }
    }

    public function loadLastApiCall()
    {
        $cacheKey = 'devices_' . $this->client . '_last_api_call';
        $this->lastApiCall = Cache::get($cacheKey);
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

        $devices = $this->devices;

        // Apply MAC search filter
        if ($this->macSearch) {
            $devices = $devices->filter(function ($device) {
                return stripos($device['macAddress'] ?? '', $this->macSearch) !== false;
            });
        }

        // Apply sorting
        if ($this->sortField === 'last_status') {
            $devices = $devices->sortBy(function ($device) {
                return isset($device['unixepoch']) && is_numeric($device['unixepoch']) ? $device['unixepoch'] : 0;
            }, SORT_REGULAR, $this->sortDirection === 'desc');
        }

        $currentPage = request()->query('page', 1);
        $paginatedDevices = new \Illuminate\Pagination\LengthAwarePaginator(
            $devices->forPage($currentPage, $this->perPage),
            $devices->count(),
            $this->perPage,
            $currentPage,
            ['path' => route('device-info')]
        );

        return view('livewire.device-info', [
            'paginatedDevices' => $paginatedDevices,
            'totalDevices' => $devices->count(),
            'timezone' => $this->timezone,
            'lastApiCall' => $this->lastApiCall
        ])->layout('layouts.app');
    }
}