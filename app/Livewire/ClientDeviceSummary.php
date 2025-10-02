<?php

namespace App\Livewire;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ClientDeviceSummary extends Component
{
    use WithPagination;

    public $clients;
    public $clientSummaries = [];
    public $allDevices = [];
    public $sortField = 'macAddress';
    public $sortDirection = 'asc';
    public $selectedDeviceMac = '';
    public $selectedDeviceDetails = [];
    public $search = '';

    protected $queryString = [
        'page' => ['except' => 1],
        'sortField' => ['except' => 'macAddress'],
        'sortDirection' => ['except' => 'asc'],
        'search' => ['except' => '']
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

    public function mount()
    {
        // Get clients associated with the authenticated user's teams
        $user = Auth::user();
        $this->clients = Client::whereIn('team_id', $user->allTeams()->pluck('id'))
            ->pluck('name', 'name')
            ->toArray();

        $this->loadSummaries();
        Log::info('ClientDeviceSummary mounted', ['clients' => $this->clients]);
    }

    public function updatedSearch($value)
    {
        Log::info('updatedSearch triggered', ['new_value' => $value]);
        $this->resetPage();
        if (empty($value)) {
            $this->allDevices = [];
        } elseif (empty($this->allDevices)) {
            $this->loadAllDevices();
        }
    }

    public function searchDevices()
    {
        Log::info('searchDevices called', ['search' => $this->search]);
        $this->resetPage();
        if (!empty($this->search) && empty($this->allDevices)) {
            $this->loadAllDevices();
        }
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->allDevices = [];
        Log::info('clearSearch called');
        $this->resetPage();
    }

    public function refreshSummaries()
    {
        $this->loadSummaries();
    }

    public function poll()
    {
        $this->loadSummaries();
    }

    public function loadSummaries()
    {
        Log::info('loadSummaries called');
        $this->clientSummaries = [];
        $redis = Redis::connection('cache');
        $rawClient = $redis->client();

        foreach (array_keys($this->clients) as $client) {
            $combinedKey = "combined_devices:{$client}";
            $rawData = $rawClient->get($combinedKey);
            $devices = $rawData ? json_decode($rawData, true) : [];
            $total = count($devices);
            $errors = 0;
            $warnings = 0;

            foreach ($devices as $device) {
                $status = isset($device['error']) && $device['error'] ? 'Error' : (isset($device['warning']) && $device['warning'] ? 'Warning' : 'OK');
                if ($status === 'Error') {
                    $errors++;
                } elseif ($status === 'Warning') {
                    $warnings++;
                }
            }

            $this->clientSummaries[$client] = [
                'total' => $total,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        Log::info('Loaded client summaries', ['summaries' => $this->clientSummaries]);
    }

    public function loadAllDevices()
    {
        Log::info('loadAllDevices called');
        $this->allDevices = [];
        $redis = Redis::connection('cache');
        $rawClient = $redis->client();

        foreach (array_keys($this->clients) as $client) {
            $combinedKey = "combined_devices:{$client}";
            $rawData = $rawClient->get($combinedKey);
            $rawDevices = $rawData ? json_decode($rawData, true) : [];

            if (is_array($rawDevices)) {
                foreach ($rawDevices as &$device) {
                    $device['client'] = $client;
                    if (isset($device['lastreboot'])) {
                        $device['lastreboot'] = Carbon::parse($device['lastreboot']);
                    }
                    if (isset($device['unixepoch']) && is_string($device['unixepoch'])) {
                        $device['unixepoch'] = (int) $device['unixepoch'];
                    }
                    $device['status'] = isset($device['error']) && $device['error'] ? 'Error' : (isset($device['warning']) && $device['warning'] ? 'Warning' : 'OK');
                }
                $this->allDevices = array_merge($this->allDevices, $rawDevices);
            }
        }

        Log::info('Loaded all devices across clients', ['devices_count' => count($this->allDevices)]);
    }

    public function render()
    {
        Log::info('Render called', [
            'clientSummaries_count' => count($this->clientSummaries),
            'allDevices_count' => count($this->allDevices),
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->getPage(),
            'search' => $this->search
        ]);

        $perPage = env('DEVICE_DEFAULT_PAGINATION', 50);
        $paginatedDevices = new LengthAwarePaginator([], 0, $perPage, 1, ['path' => url()->current(), 'pageName' => 'page']);

        if (!empty($this->search)) {
            $filteredDevices = collect($this->allDevices);

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
                    str_contains(strtolower($device['status'] ?? ''), $searchTerm) ||
                    str_contains(strtolower($device['client'] ?? ''), $searchTerm);
            });

            $sortedDevices = $filteredDevices->sortBy(
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
        }

        return view('livewire.pages.devices.client-device-summary', [
            'paginatedDevices' => $paginatedDevices,
            'clientSummaries' => $this->clientSummaries
        ])->layout('layouts.app');
    }
}