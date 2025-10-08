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
use Illuminate\Support\Facades\Auth;


class DeviceInfo extends Component
{
    use WithPagination;

    public $selectedClient = '';
    public $clients;
    public $allDevices = [];
    public $loading = false;
    public $sortField = 'macAddress';
    public $sortDirection = 'asc';
    public $selectedDeviceMac = '';
    public $selectedDeviceDetails = [];
    public $search = '';
    public $statusFilter = ''; // Added for status filtering

    public $client;        // string from URL
    public $status;        // status filter from URL
    protected $queryString = [
        'page' => ['except' => 1],
        'sortField' => ['except' => 'macAddress'],
        'sortDirection' => ['except' => 'asc'],
        'selectedClient' => ['except' => ''],
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''], // Added to query string
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function showDeviceDetails($macAddress)
    {
        $this->selectedDeviceMac = $macAddress;
        $this->selectedDeviceDetails = collect($this->allDevices)->firstWhere('macAddress', $macAddress) ?? [];
        Log::info('showDeviceDetails called', [
            'macAddress' => $macAddress,
            'selectedDeviceDetails' => $this->selectedDeviceDetails
        ]);
    }

    public function closeModal()
    {
        $this->selectedDeviceMac = '';
        $this->selectedDeviceDetails = [];
    }


    public function mount($client = null, $status = null)
    {
        // Get clients associated with the authenticated user's teams
        $user = Auth::user();
        $this->clients = Client::whereIn('team_id', $user->allTeams()->pluck('id'))
            ->pluck('name', 'name')
            ->toArray();

        $this->status = $status;

        // If route client exists in the user's clients, use it; otherwise default to first
        if ($client && array_key_exists($client, $this->clients)) {
            $this->selectedClient = $client;
        } else {
            $this->selectedClient = array_key_first($this->clients) ?? '';
        }

        $this->statusFilter = $status;

        Log::info('Component mounted', [
            'route_client' => $client,
            'selectedClient' => $this->selectedClient,
            'status' => $this->status,
            'clients' => $this->clients,
        ]);

        if (!empty($this->selectedClient)) {
            $this->loadDevices();
        }
    }

    public function updatedSelectedClient($value)
    {
        Log::info('updatedSelectedClient triggered', ['new_value' => $value]);
        $this->search = ''; // Reset search when client changes
        $this->statusFilter = ''; // Reset status filter when client changes
        $this->resetPage();
        $this->loadDevices();
    }


    public function refreshDevices()
    {
        Artisan::call('devices:fetch');
        Artisan::call('device-details:fetch');

        // optionally reload devices after refresh
        $this->loadDevices();

        session()->flash('message', 'Devices refreshed successfully!');
    }

    public function refreshData()
    {
        if (empty($this->selectedClient)) {
            return;
        }
        $this->loading = true;
        Artisan::call('devices:fetch --client=' . $this->selectedClient);
        Artisan::call('device-details:fetch --client=' . $this->selectedClient);
        $this->loadDevices();
        $this->loading = false;
    }

    public function manualLoad()
    {
        Log::info('manualLoad called', ['selectedClient' => $this->selectedClient]);
        $this->loadDevices();
    }

    public function searchDevices()
    {
        Log::info('searchDevices called', ['search' => $this->search, 'selectedClient' => $this->selectedClient, 'statusFilter' => $this->statusFilter]);
        $this->resetPage();
        $this->loadDevices();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->statusFilter = ''; // Reset status filter when clearing search
        Log::info('clearSearch called', ['selectedClient' => $this->selectedClient]);
        $this->resetPage();
        $this->loadDevices();
    }

    public function poll()
    {
        $this->loadDevices();
    }



    public function loadDevices()
    {
        Log::info('loadDevices called', [
            'selectedClient' => $this->selectedClient,
            'search' => $this->search,
            'statusFilter' => $this->statusFilter
        ]);

        if (empty($this->selectedClient)) {
            $this->allDevices = [];
            Log::info('loadDevices: Client empty, setting allDevices to []');
            return;
        }

        // Load combined devices from Redis
        $redis = Redis::connection('cache');
        $combinedKey = "combined_devices:{$this->selectedClient}";
        $rawClient = $redis->client();
        $rawData = $rawClient->get($combinedKey);
        $rawDevices = $rawData ? json_decode($rawData, true) : [];

        if (is_array($rawDevices)) {
            foreach ($rawDevices as &$device) {
                if (isset($device['lastreboot'])) {
                    $device['lastreboot'] = Carbon::parse($device['lastreboot']);
                }
                if (isset($device['unixepoch']) && is_string($device['unixepoch'])) {
                    $device['unixepoch'] = (int) $device['unixepoch'];
                }
                $device['status'] = isset($device['error']) && $device['error']
                    ? 'Error'
                    : (isset($device['warning']) && $device['warning'] ? 'Warning' : 'OK');
            }
        }

        $this->allDevices = $rawDevices ?? [];

        Log::info('Loaded combined devices from Redis', [
            'client' => $this->selectedClient,
            'devices_count' => count($this->allDevices)
        ]);
    }

    public function render()
    {
        Log::info('Render called', [
            'allDevices_count' => count($this->allDevices),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->getPage(),
            'search' => $this->search,
            'statusFilter' => $this->statusFilter
        ]);

        $perPage = env('DEVICE_DEFAULT_PAGINATION', 50);

        // Filter devices based on search term
        $filteredDevices = collect($this->allDevices);
        if (!empty($this->search)) {
            $searchTerm = strtolower(trim($this->search));
            $filteredDevices = $filteredDevices->filter(function ($device) use ($searchTerm) {
                return str_contains(strtolower($device['macAddress'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['display_name'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['device_version'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['site_name'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['model'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['operatingSystem'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['firmwareVersion'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['lastreboot'] ? $device['lastreboot']->format('Y-m-d H:i:s') : ''), $searchTerm) ||
                    str_contains(strtolower($device['unixepoch'] ? \Carbon\Carbon::createFromTimestampMs($device['unixepoch'])->format('Y-m-d H:i:s') : ''), $searchTerm) ||
                    str_contains(strtolower($device['status'] ?? ''), $searchTerm);
            });
        }

        // Apply status filter if set
        if (!empty($this->statusFilter)) {
            $filteredDevices = $filteredDevices->filter(function ($device) {
                if ($this->statusFilter === 'down') {
                    return in_array($device['status'], ['Error', 'Warning']);
                } else {
                    return $device['status'] === $this->statusFilter;
                }
            });
        }

        // Apply sorting
        $sortedDevices = $filteredDevices->sortBy(
            $this->sortField,
            SORT_REGULAR,
            $this->sortDirection === 'desc'
        );

        // Paginate the results
        $paginatedDevices = new LengthAwarePaginator(
            $sortedDevices->forPage($this->getPage(), $perPage),
            $sortedDevices->count(),
            $perPage,
            $this->getPage(),
            ['path' => url()->current(), 'pageName' => 'page']
        );

        return view('livewire.pages.devices.device-info', [
            'paginatedDevices' => $paginatedDevices,
            'clients' => $this->clients
        ])->layout('layouts.app');
    }

}