<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Device Information</h1>

    <div class="mb-4 flex space-x-4">
        <!-- Client Selector -->
        <div class="flex-1">
            <label for="client" class="block text-sm font-bold text-gray-900">Select Client</label>
            <select wire:model.debounce.500ms="client" id="client"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="">Select a client</option>
                @foreach ($clients as $client)
                    <option value="{{ $client }}">{{ $client }}</option>
                @endforeach
            </select>
        </div>

        <!-- Timezone Selector -->
        <div class="flex-1">
            <label for="timezone" class="block text-sm font-bold text-gray-900">Select Timezone</label>
            <select wire:model.debounce.500ms="timezone" id="timezone"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option value="America/New_York">Eastern (ET)</option>
                <option value="America/Chicago">Central (CT)</option>
                <option value="America/Denver">Mountain (MT)</option>
                <option value="America/Phoenix">Arizona (MST)</option>
                <option value="America/Los_Angeles">Pacific (PT)</option>
                <option value="America/Anchorage">Alaska (AKT)</option>
                <option value="Pacific/Honolulu">Hawaii (HST)</option>
            </select>
        </div>

        <!-- Refresh Button -->
        <div class="flex items-end">
            <button 
                wire:click="refreshDevices"
                class="mt-1 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                Refresh
            </button>
        </div>
    </div>

    <!-- MAC Search -->
    <div class="mb-4">
        <label for="macSearch" class="block text-sm font-bold text-gray-900">Search MAC Address</label>
        <input 
            wire:model.live.debounce.500ms="macSearch"
            id="macSearch"
            type="text"
            placeholder="Enter MAC Address (e.g., 00:1A:2B)"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
        >
    </div>

    <!-- Last API Call Time -->
    @if ($lastApiCall)
        <div class="mb-4 text-sm text-gray-600">
            Last API Call: {{ \Carbon\Carbon::parse($lastApiCall)->tz($timezone)->format('m/d/y h:i:s A') }}
        </div>
    @endif

    <div class="relative">
        <!-- Loading Indicator Overlay -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center bg-gray-100 bg-opacity-75 z-10">
            <svg class="animate-spin h-8 w-8 text-indigo-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-gray-600 text-base font-medium">Loading devices...</span>
        </div>

        <!-- Fallback CSS for Animation -->
        <style>
            .animate-spin-fallback {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }
                to {
                    transform: rotate(360deg);
                }
            }
        </style>

        @if ($error)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif

        @if ($paginatedDevices->count() > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Device List ({{ $totalDevices }} Total)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Client</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Operating System</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    MAC</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Model</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Firmware Version</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Screenshot</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Oopsscreen</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Reboot UTC</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                    wire:click="sortBy('last_status')">
                                    Last Status
                                    @if ($sortField === 'last_status')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($paginatedDevices as $device)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $device->client ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $device->operatingSystem ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $device->macAddress ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $device->model ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $device->firmwareVersion ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($device->screenshot)
                                            <a href="{{ $device->screenshot }}" target="_blank"
                                                class="text-indigo-600 hover:text-indigo-900">View</a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $device->oopsscreen ?? 'N/A' }}</td>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($device->lastreboot)
                                            {{ $device->lastreboot->format('y/m/d H:i') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($device->unixepoch)
                                            {{-- {{ \Carbon\Carbon::createFromTimestamp($device->unixepoch)->tz($timezone)->format('y/m/d H:i:s') }} --}}
                                            {{-- {{ \Carbon\Carbon::createFromTimestamp($device->unixepoch)->format('y/m/d H:i:s') }} --}}
                                            {{ \App\Utils\TimestampFormatter::format($device->unixepoch, $timezone) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Links -->
            <div class="mt-4">
                {{ $paginatedDevices->links() }}
            </div>
        @else
            <div class="bg-gray-100 border border-gray-400 text-gray-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">No devices available. Please select a client.</span>
            </div>
        @endif
    </div>

    <!-- Livewire 3 Event Listener -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('refresh', () => {
                console.log('Devices refreshed');
            });
        });
    </script>
</div>