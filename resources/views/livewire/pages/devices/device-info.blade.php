<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">Device Information</h1>
                            <p class="mt-2 text-sm text-gray-700">A list of all devices for the selected client, pulled
                                from cache.</p>
                            @if($selectedClient)
                                <p class="mt-2 text-sm font-medium text-gray-900">Selected Client: {{ $selectedClient }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    Last API Call:
                                    {{ \Illuminate\Support\Facades\Cache::get("devices_{$selectedClient}_last_api_call") ? \Carbon\Carbon::parse(\Illuminate\Support\Facades\Cache::get("devices_{$selectedClient}_last_api_call"))->format('Y-m-d H:i:s') : 'N/A' }}
                                </p>
                            @else
                                <p class="mt-2 text-sm text-gray-600">No client selected.</p>
                            @endif
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                            @if($selectedClient)
                                <button wire:click="refreshData" wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25">
                                    @if($loading)
                                        Refreshing...
                                    @else
                                        Refresh API Data
                                    @endif
                                </button>
                                <button wire:click="manualLoad"
                                    class="ml-2 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Manual Load
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Client Selection Dropdown --}}
                    <div class="mt-4 mb-6">
                        <label for="selectedClient" class="block text-sm font-medium text-gray-700 mb-2">Select
                            Client</label>
                        <select id="selectedClient" wire:model.live="selectedClient" wire:change="loadDevices"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- No Client Selected --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client }}" {{ $selectedClient == $client ? 'selected' : '' }}>{{ $client }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Debug Info (remove after fixing) --}}
                        <p class="mt-2 text-sm text-gray-600">Debug: Selected Client = "{{ $selectedClient }}", Total
                            Devices = {{ count($allDevices) }}, Page Devices = {{ $paginatedDevices->count() }}</p>
                    </div>

                    <div class="mt-8 flow-root">
                        @if($paginatedDevices->isEmpty())
                            <p class="text-gray-500">No devices found for the selected client. Select a client to view data.
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                <button wire:click="sortBy('macAddress')"
                                                    class="flex items-center space-x-1">
                                                    <span>MAC Address</span>
                                                    @if($sortField === 'macAddress')
                                                        <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                    @endif
                                                </button>
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                <button wire:click="sortBy('model')" class="flex items-center space-x-1">
                                                    <span>Model</span>
                                                    @if($sortField === 'model')
                                                        <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                    @endif
                                                </button>
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                <button wire:click="sortBy('operatingSystem')"
                                                    class="flex items-center space-x-1">
                                                    <span>OS</span>
                                                    @if($sortField === 'operatingSystem')
                                                        <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                    @endif
                                                </button>
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                <button wire:click="sortBy('firmwareVersion')"
                                                    class="flex items-center space-x-1">
                                                    <span>Firmware</span>
                                                    @if($sortField === 'firmwareVersion')
                                                        <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                    @endif
                                                </button>
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                <button wire:click="sortBy('lastreboot')"
                                                    class="flex items-center space-x-1">
                                                    <span>Last Reboot</span>
                                                    @if($sortField === 'lastreboot')
                                                        <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                    @endif
                                                </button>
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                <button wire:click="sortBy('unixepoch')"
                                                    class="flex items-center space-x-1">
                                                    <span>Last Ping </span>
                                                    @if($sortField === 'unixepoch')
                                                        <span>{!! $sortDirection === 'asc' ? '&uarr;' : '&darr;' !!}</span>
                                                    @endif
                                                </button>
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Screenshot
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($paginatedDevices as $device)
                                            <tr class="hover:bg-gray-50">
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $device['macAddress'] ?? 'Unknown MAC' }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $device['model'] ?? 'N/A' }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $device['operatingSystem'] ?? 'N/A' }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $device['firmwareVersion'] ?? 'N/A' }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    {{ $device['lastreboot'] ? $device['lastreboot']->format('Y-m-d H:i:s') : 'N/A' }}
                                                </td>
                                                <td class="px-3 py-4 text-sm text-gray-500 min-w-[120px]">
                                                    {{ $device['unixepoch'] ? \Carbon\Carbon::createFromTimestampMs($device['unixepoch'])->format('Y-m-d H:i:s') : 'N/A' }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    <div class="flex space-x-2">
                                                        @if($device['warning'])
                                                            <span
                                                                class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">Warning</span>
                                                        @endif
                                                        @if($device['error'])
                                                            <span
                                                                class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">Error</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                    @if($device['screenshot'])
                                                        <a href="{{ $device['screenshot'] }}" target="_blank"
                                                            rel="noopener noreferrer">
                                                            <button type="button"
                                                                class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                                View
                                                            </button>
                                                        </a>
                                                    @else
                                                        <span class="text-gray-400">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination Links --}}
                            @if ($paginatedDevices->hasPages())
                                <div class="mt-6 flex justify-center items-center space-x-2">
                                    {{-- Previous Button --}}
                                    <button wire:click="previousPage" wire:loading.attr="disabled"
                                        class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                        {{ $paginatedDevices->onFirstPage() ? 'disabled' : '' }}>
                                        Previous
                                    </button>

                                    {{-- Page Numbers with Truncation --}}
                                    @php
                                        $currentPage = $paginatedDevices->currentPage();
                                        $lastPage = $paginatedDevices->lastPage();
                                        $window = 2; // Number of pages to show on each side of current page
                                        $startPage = max(1, $currentPage - $window);
                                        $endPage = min($lastPage, $currentPage + $window);

                                        // Adjust start/end to ensure at least 5 pages are shown (if possible)
                                        if ($endPage - $startPage < 4) {
                                            if ($currentPage <= $window + 1) {
                                                $endPage = min($lastPage, $startPage + 4);
                                            } else {
                                                $startPage = max(1, $endPage - 4);
                                            }
                                        }
                                    @endphp

                                    {{-- First Page --}}
                                    @if ($startPage > 1)
                                        <button wire:click="gotoPage(1)"
                                            class="inline-flex items-center px-4 py-2 {{ $currentPage == 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            1
                                        </button>
                                        @if ($startPage > 2)
                                            <span class="px-2 py-1 text-gray-500">...</span>
                                        @endif
                                    @endif

                                    {{-- Page Range --}}
                                    @foreach (range($startPage, $endPage) as $page)
                                        <button wire:click="gotoPage({{ $page }})"
                                            class="inline-flex items-center px-4 py-2 {{ $page == $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            {{ $page }}
                                        </button>
                                    @endforeach

                                    {{-- Last Page --}}
                                    @if ($endPage < $lastPage)
                                        @if ($endPage < $lastPage - 1)
                                            <span class="px-2 py-1 text-gray-500">...</span>
                                        @endif
                                        <button wire:click="gotoPage({{ $lastPage }})"
                                            class="inline-flex items-center px-4 py-2 {{ $currentPage == $lastPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            {{ $lastPage }}
                                        </button>
                                    @endif

                                    {{-- Next Button --}}
                                    <button wire:click="nextPage" wire:loading.attr="disabled"
                                        class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25"
                                        {{ $paginatedDevices->onLastPage() ? 'disabled' : '' }}>
                                        Next
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>