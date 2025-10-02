<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="px-4 sm:px-6 lg:px-8">
                        <div class="sm:flex sm:items-center">
                            <div class="sm:flex-auto">
                                <h1 class="text-base font-semibold leading-6 text-gray-900">Client Device Summaries</h1>
                                <p class="mt-2 text-sm text-gray-700">Summaries of devices for all accessible clients, pulled from cache.</p>
                            </div>
                            <div class="mt-4 sm:mt-0 sm:ml-4">
                                <button wire:click="refreshSummaries" wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25">
                                    Refresh Summaries
                                </button>
                            </div>
                        </div>

                        {{-- Spinner for Loading States --}}
                        <div wire:loading wire:loading.delay class="mt-4 flex justify-center">
                            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0  refuted4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        {{-- Client Summaries Table --}}
                        <div class="mt-8 flow-root" wire:poll.10s="poll">
                            @if(empty($clientSummaries))
                                <p class="text-gray-500">No clients found.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Client</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Total Devices</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Devices in Error</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Devices in Warning</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @foreach($clientSummaries as $client => $summary)
                                                <tr>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $client }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $summary['total'] }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $summary['errors'] }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $summary['warnings'] }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                                        <a href="{{ route('device-info', ['selectedClient' => urlencode($client)]) }}"
                                                            class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                            View All
                                                        </a>
                                                        <a href="{{ route('device-info', ['selectedClient' => urlencode($client), 'statusFilter' => 'Error']) }}"
                                                            class="ml-2 inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                            View Down
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- Search Input --}}
                        <div class="mt-6 mb-6">
                            <div class="flex flex-col sm:flex-row sm:items-end sm:space-x-4">
                                <div class="flex-1">
                                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Devices Across All Clients</label>
                                    <div class="flex rounded-md shadow-sm">
                                        <input type="text" id="search" wire:model.live.debounce.500ms="search" placeholder="Search devices..."
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <button wire:click="searchDevices" wire:loading.attr="disabled"
                                            class="ml-2 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25">
                                            Search
                                        </button>
                                        <button wire:click="clearSearch" wire:loading.attr="disabled"
                                            class="ml-2 inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 focus:bg-gray-500 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Debug Info --}}
                            <p class="mt-2 text-sm text-gray-600">
                                Debug: Total Clients = {{ count($clientSummaries) }},
                                Search = "{{ $search }}",
                                Search Results Count = {{ $paginatedDevices->count() }}
                            </p>
                        </div>

                        {{-- Search Results --}}
                        <div class="mt-8 flow-root">
                            @if($paginatedDevices->isEmpty())
                                @if(!empty($search))
                                    <p class="text-gray-500">No devices found matching your search.</p>
                                @endif
                            @else
                                <h2 class="text-base font-semibold leading-6 text-gray-900 mb-4">Search Results</h2>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('client')" class="flex items-center space-x-1">
                                                        <span>Client</span>
                                                        @if($sortField === 'client')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('macAddress')" class="flex items-center space-x-1">
                                                        <span>MAC Address</span>
                                                        @if($sortField === 'macAddress')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('display_name')" class="flex items-center space-x-1">
                                                        <span>Display Name</span>
                                                        @if($sortField === 'display_name')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('device_version')" class="flex items-center space-x-1">
                                                        <span>Device Version</span>
                                                        @if($sortField === 'device_version')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('site_name')" class="flex items-center space-x-1">
                                                        <span>Site Name</span>
                                                        @if($sortField === 'site_name')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('model')" class="flex items-center space-x-1">
                                                        <span>Model</span>
                                                        @if($sortField === 'model')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('operatingSystem')" class="flex items-center space-x-1">
                                                        <span>Operating System</span>
                                                        @if($sortField === 'operatingSystem')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('firmwareVersion')" class="flex items-center space-x-1">
                                                        <span>Firmware Version</span>
                                                        @if($sortField === 'firmwareVersion')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('lastreboot')" class="flex items-center space-x-1">
                                                        <span>Last Reboot</span>
                                                        @if($sortField === 'lastreboot')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('unixepoch')" class="flex items-center space-x-1">
                                                        <span>Last Ping</span>
                                                        @if($sortField === 'unixepoch')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                    <button wire:click="sortBy('status')" class="flex items-center space-x-1">
                                                        <span>Status</span>
                                                        @if($sortField === 'status')
                                                            <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                        @endif
                                                    </button>
                                                </th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Screenshot</th>
                                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Details</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @foreach($paginatedDevices as $device)
                                                <tr>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $device['client'] ?? 'N/A' }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">{{ $device['macAddress'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['display_name'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['device_version'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['site_name'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['model'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['operatingSystem'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['firmwareVersion'] ?? 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['lastreboot'] ? $device['lastreboot']->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                                    <td class="px-3 py-4 text-sm text-gray-900">{{ $device['unixepoch'] ? \Carbon\Carbon::createFromTimestampMs($device['unixepoch'])->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                                        @if($device['status'] === 'Error')
                                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">{{ $device['status'] }}</span>
                                                        @elseif($device['status'] === 'Warning')
                                                            <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">{{ $device['status'] }}</span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">{{ $device['status'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                                        @if(isset($device['screenshot']))
                                                            <a href="{{ $device['screenshot'] }}" target="_blank" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                View
                                                            </a>
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                                        <button wire:click="showDeviceDetails('{{ $device['macAddress'] }}')"
                                                            class="inline-flex items-center px-2.5 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                            View
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination Links --}}
                                @if ($paginatedDevices->hasPages())
                                    <div class="mt-6 flex justify-center items-center space-x-2">
                                        <button wire:click="previousPage" wire:loading.attr="disabled"
                                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                            {{ $paginatedDevices->onFirstPage() ? 'disabled' : '' }}>
                                            Previous
                                        </button>

                                        @php
                                            $currentPage = $paginatedDevices->currentPage();
                                            $lastPage = $paginatedDevices->lastPage();
                                            $window = 2;
                                            $startPage = max(1, $currentPage - $window);
                                            $endPage = min($lastPage, $currentPage + $window);
                                            if ($endPage - $startPage < 4) {
                                                if ($currentPage <= $window + 1) {
                                                    $endPage = min($lastPage, $startPage + 4);
                                                } else {
                                                    $startPage = max(1, $endPage - 4);
                                                }
                                            }
                                        @endphp

                                        @if ($startPage > 1)
                                            <button wire:click="gotoPage(1)"
                                                class="inline-flex items-center px-4 py-2 {{ $currentPage == 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                1
                                            </button>
                                            @if ($startPage > 2)
                                                <span class="px-2 py-1 text-gray-500">...</span>
                                            @endif
                                        @endif

                                        @foreach (range($startPage, $endPage) as $page)
                                            <button wire:click="gotoPage({{ $page }})"
                                                class="inline-flex items-center px-4 py-2 {{ $page == $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                {{ $page }}
                                            </button>
                                        @endforeach

                                        @if ($endPage < $lastPage)
                                            @if ($endPage < $lastPage - 1)
                                                <span class="px-2 py-1 text-gray-500">...</span>
                                            @endif
                                            <button wire:click="gotoPage({{ $lastPage }})"
                                                class="inline-flex items-center px-4 py-2 {{ $currentPage == $lastPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                {{ $lastPage }}
                                            </button>
                                        @endif

                                        <button wire:click="nextPage" wire:loading.attr="disabled"
                                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                            {{ $paginatedDevices->onLastPage() ? 'disabled' : '' }}>
                                            Next
                                        </button>
                                    </div>
                                @endif

                                {{-- Modal for Device Details --}}
                                @if($selectedDeviceMac)
                                    <div x-data="{ open: true }" x-show="open" x-transition class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" x-on:click="open = false"></div>
                                            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                                                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                                    <div class="sm:flex sm:items-start">
                                                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                                                Device Details for MAC: {{ $selectedDeviceMac }}
                                                            </h3>
                                                            <div class="mt-2">
                                                                <pre class="text-sm text-gray-600 overflow-auto max-h-96">{{ json_encode($selectedDeviceDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                    <button wire:click="closeModal" type="button"
                                                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                                        Close
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>