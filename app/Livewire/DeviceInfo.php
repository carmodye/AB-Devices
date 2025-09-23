<?php

namespace App\Livewire;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceInfo extends Component
{
    use WithPagination;

    public $selectedClient = ''; // Default to no client
    public $clients;
    public $allDevices = []; // Serializable array of all devices for this client
    public $loading = false;
    public $sortField = 'macAddress'; // Default sort field
    public $sortDirection = 'asc'; // Default sort direction

    // Define query string parameters to persist state in URL
    protected $queryString = [
        'page' => ['except' => 1],
        'sortField' => ['except' => 'macAddress'],
        'sortDirection' => ['except' => 'asc'],
        'selectedClient' => ['except' => ''], // Persist selected client
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            // Toggle direction if clicking the same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new sort field and default to ascending
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Reset pagination to page 1 when sorting
        $this->resetPage();
    }

    public function mount()
    {
        $this->clients = Client::pluck('name', 'name')->toArray(); // From DB
        $this->selectedClient = array_key_first($this->clients) ?? ''; // Auto-select first if available
        Log::info('Component mounted', ['selectedClient' => $this->selectedClient, 'clients' => $this->clients]);
        if (!empty($this->selectedClient)) {
            $this->loadDevices();
        }
    }

    public function updatedSelectedClient($value)
    {
        Log::info('updatedSelectedClient triggered', ['new_value' => $value]);
        $this->resetPage(); // Reset pagination when client changes
        $this->loadDevices(); // Reloads allDevices; pagination handled in render
    }

    public function refreshData()
    {
        if (empty($this->selectedClient)) {
            return;
        }

        $this->loading = true;

        // Run the artisan command to refresh API data for the selected client
        Artisan::call('devices:fetch --client=' . $this->selectedClient);

        // Reload devices from Redis
        $this->loadDevices();

        $this->loading = false;
    }

    public function manualLoad()
    {
        Log::info('manualLoad called', ['selectedClient' => $this->selectedClient]);
        $this->loadDevices();
    }

    public function loadDevices()
    {
        Log::info('loadDevices called', ['selectedClient' => $this->selectedClient]);
        if (empty($this->selectedClient)) {
            $this->allDevices = [];
            Log::info('loadDevices: Client empty, setting allDevices to []');
            return;
        }

        $redis = Redis::connection('cache');
        $clientKey = "devices:{$this->selectedClient}"; // Bare key
        Log::info('Redis key', ['clientKey' => $clientKey]);

        $fullKey = $clientKey; // Full key for raw get
        Log::info('Full Redis key', ['fullKey' => $fullKey]);

        $rawClient = $redis->client(); // Raw phpredis client
        $rawData = $rawClient->get($fullKey); // Get with full key, no additional prefix
        Log::info('Redis raw data', ['rawData' => substr($rawData ?? '', 0, 100)]);

        Log::info('Redis raw get result', [
            'full_key' => $fullKey,
            'rawData_length' => strlen($rawData ?? ''),
            'is_null' => is_null($rawData),
            'rawData_preview' => substr($rawData ?? '', 0, 100)
        ]);

        $rawDevices = $rawData ? json_decode($rawData, true) : [];
        Log::info('JSON decode result', [
            'decoded_count' => count($rawDevices ?? []),
            'json_error' => json_last_error(),
            'first_device' => $rawDevices[0] ?? 'none'
        ]);

        // Parse lastreboot and unixepoch for each device
        if (is_array($rawDevices)) {
            foreach ($rawDevices as &$device) {
                if (isset($device['lastreboot'])) {
                    $device['lastreboot'] = Carbon::parse($device['lastreboot']);
                }
                if (isset($device['unixepoch']) && is_string($device['unixepoch'])) {
                    $device['unixepoch'] = (int) $device['unixepoch'];
                }
            }
        }

        $this->allDevices = $rawDevices ?? []; // Serializable array

        Log::info('Loaded devices from Redis', [
            'client' => $this->selectedClient,
            'full_key' => $fullKey,
            'retrieved_count' => count($rawDevices ?? []),
            'first_mac' => is_array($rawDevices) && isset($rawDevices[0]) ? $rawDevices[0]['macAddress'] : 'none',
        ]);
    }

    public function render()
    {
        Log::info('Render called', [
            'allDevices_count' => count($this->allDevices),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->page ?? 'not set'
        ]);

        $perPage = 30;

        // Sort the devices
        $sortedDevices = collect($this->allDevices)->sortBy(
            $this->sortField,
            SORT_REGULAR,
            $this->sortDirection === 'desc'
        );

        // Paginate using LengthAwarePaginator
        $paginatedDevices = new LengthAwarePaginator(
            $sortedDevices->forPage($this->getPage(), $perPage), // Use getPage() from WithPagination
            $sortedDevices->count(),
            $perPage,
            $this->getPage(), // Use getPage() to ensure compatibility
            ['path' => url()->current(), 'pageName' => 'page']
        );

        return view('livewire.pages.devices.device-info', [
            'paginatedDevices' => $paginatedDevices,
            'clients' => $this->clients,
        ])->layout('layouts.app');
    }
}