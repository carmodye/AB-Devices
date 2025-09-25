<?php

namespace App\Livewire;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceInfo extends Component
{
    use WithPagination;

    public $selectedClient = '';
    public $clients;
    public $allDevices = [];
    public $deviceDetails = [];
    public $loading = false;
    public $sortField = 'macAddress';
    public $sortDirection = 'asc';
    public $selectedDeviceMac = '';
    public $selectedDeviceDetails = [];

    protected $queryString = [
        'page' => ['except' => 1],
        'sortField' => ['except' => 'macAddress'],
        'sortDirection' => ['except' => 'asc'],
        'selectedClient' => ['except' => '']
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
        $this->selectedDeviceDetails = $this->deviceDetails[strtoupper($macAddress)] ?? [];
    }

    public function closeModal()
    {
        $this->selectedDeviceMac = '';
        $this->selectedDeviceDetails = [];
    }

    public function mount()
    {
        // Get clients associated with the authenticated user's teams
        $user = Auth::user();
        $this->clients = Client::whereIn('team_id', $user->allTeams()->pluck('id'))
            ->pluck('name', 'name')
            ->toArray();

        // Set default selected client to the first available client, if any
        $this->selectedClient = array_key_first($this->clients) ?? '';
        Log::info('Component mounted', ['selectedClient' => $this->selectedClient, 'clients' => $this->clients]);

        if (!empty($this->selectedClient)) {
            $this->loadDevices();
        }
    }

    public function updatedSelectedClient($value)
    {
        Log::info('updatedSelectedClient triggered', ['new_value' => $value]);
        $this->resetPage();
        $this->loadDevices();
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

    public function loadDevices()
    {
        Log::info('loadDevices called', ['selectedClient' => $this->selectedClient]);
        if (empty($this->selectedClient)) {
            $this->allDevices = [];
            $this->deviceDetails = [];
            Log::info('loadDevices: Client empty, setting allDevices to []');
            return;
        }

        // Load basic devices
        $redis = Redis::connection('cache');
        $clientKey = "devices:{$this->selectedClient}";
        $rawClient = $redis->client();
        $rawData = $rawClient->get($clientKey);
        $rawDevices = $rawData ? json_decode($rawData, true) : [];
        if (is_array($rawDevices)) {
            foreach ($rawDevices as &$device) {
                if (isset($device['lastreboot'])) {
                    $device['lastreboot'] = Carbon::parse($device['lastreboot']);
                }
                if (isset($device['unixepoch']) && is_string($device['unixepoch'])) {
                    $device['unixepoch'] = (int) $device['unixepoch'];
                }
                $device['status'] = $device['error'] ? 'Error' : ($device['warning'] ? 'Warning' : 'OK');
            }
        }
        $this->allDevices = $rawDevices ?? [];

        // Load details
        $detailsKey = "device_details:{$this->selectedClient}";
        $detailsRaw = $rawClient->get($detailsKey);
        $this->deviceDetails = $detailsRaw ? json_decode($detailsRaw, true) : [];
        Log::info('Loaded details from Redis', [
            'client' => $this->selectedClient,
            'details_key' => $detailsKey,
            'details_count' => count($this->deviceDetails),
            'sample_detail_keys' => array_slice(array_keys($this->deviceDetails), 0, 3)
        ]);

        // Merge details into allDevices
        foreach ($this->allDevices as &$device) {
            $mac = strtoupper(trim($device['macAddress'] ?? '')); // Use macAddress from basic devices
            $detail = $this->deviceDetails[$mac] ?? [];
            $device['display_name'] = $detail['display_name'] ?? 'N/A';
            $device['device_version'] = $detail['device_version'] ?? 'N/A';
            $device['site_name'] = $detail['site_name'] ?? 'N/A';
            Log::debug('Merged device', [
                'mac_original' => $device['macAddress'] ?? 'N/A',
                'mac_upper_trim' => $mac,
                'detail_found' => !empty($detail),
                'display_name' => $device['display_name'],
                'device_version' => $device['device_version'],
                'site_name' => $device['site_name']
            ]);
        }

        Log::info('Loaded devices and details from Redis', [
            'client' => $this->selectedClient,
            'devices_count' => count($this->allDevices),
            'details_count' => count($this->deviceDetails),
            'matched_count' => count(array_filter($this->allDevices, fn($d) => $d['display_name'] !== 'N/A'))
        ]);
    }

    public function render()
    {
        Log::info('Render called', [
            'allDevices_count' => count($this->allDevices),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->getPage()
        ]);

        //$perPage = 10;

        $perPage = env('DEVICE_DEFAULT_PAGINATION', 10);

        $sortedDevices = collect($this->allDevices)->sortBy(
            $this->sortField,
            SORT_REGULAR,
            $this->sortDirection === 'desc'
        );

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