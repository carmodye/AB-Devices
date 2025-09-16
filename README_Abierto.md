Base project using 
- laravel jetstream with livewire and tailwind 
- simple CRUD with notes
- team install 

added debug bar

composer require barryvdh/laravel-debugbar --dev

should work but can add this to providers:
Barryvdh\Debugbar\ServiceProvider::class,

to add if we want to customize
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"


Call to device info lambda stores in redis cache.

Configured to run in Docker with 3 images using sail


added crontab initiated event to load cache in docker need to run it by hand from tinker

run
php artisan tinker
then:
> $command = new \App\Console\Commands\FetchDeviceData;
= App\Console\Commands\FetchDeviceData {#5776}

> $command->handle();


in Docker terminal do:
php artisan tinker 
Artisan::call('devices:fetch', ['--verbose' => true]);


Need to fix:
- screen first needs to check cache if empty for all clients needs to call command to load it
- large device issue display





To create a new Livewire component called `OpenServers` that refreshes all clients' device data and displays a grid with columns for `Client`, `Devices Count`, `Warnings` (count of devices not responding in the last 10 minutes), and `Offline` (count of devices where `oopsscreen` is `"true"`, `"N/A"`, or blank/null), we’ll build the component, its view, and integrate it with the existing `FetchDeviceData` command and database setup. This will leverage the server-side pagination, `oopsscreen` as a string, and the `devices:fetch` command, ensuring performance for large datasets and compatibility with Laravel Sail.

### Analysis

- **Requirements**:
  - **Component**: A Livewire component named `OpenServers` (route `/open-servers`).
  - **Refresh on Render**: On each render, trigger the `devices:fetch` command to refresh all clients' device data.
  - **Grid Display**:
    - Columns: `Client`, `Devices Count` (total devices per client), `Warnings` (devices not responding in the last 10 minutes, based on `unixepoch`), `Offline` (devices where `oopsscreen` is `"true"`, `"N/A"`, or null/blank).
    - Use Tailwind CSS for styling, consistent with `device-info.blade.php` (e.g., `text-gray-900 font-bold` for labels).
  - **Integration**:
    - Use the `devices` table (`client`, `operatingSystem`, `macAddress`, `model`, `firmwareVersion`, `screenshot`, `oopsscreen`, `lastreboot`, `unixepoch`).
    - Call `devices:fetch` to ensure fresh data.
    - Maintain performance for large datasets (e.g., thousands of devices per client).

- **Context**:
  - **Database**: The `devices` table has `oopsscreen` as a `varchar` (string), storing `"true"`, `"false"`, `"N/A"`, or null/blank, per your latest update.
  - **Scheduler**: `devices:fetch` runs every 5 minutes via a Sail while loop (`while true; do php artisan schedule:run; sleep 60; done`).
  - **Existing Fixes**:
    - `DEVICE_API_URL` issue resolved (set in `.env`).
    - `FetchDeviceData` supports `--client` option.
    - Mass assignment fixed in `Device` model.
    - `DeviceInfo` uses server-side pagination.
  - **Assumptions**:
    - `unixepoch` is a Unix timestamp (in milliseconds) for the last device response.
    - Not responding in the last 10 minutes means `unixepoch` is older than `now() - 10 minutes` or null.
    - `oopsscreen` values `"true"`, `"N/A"`, or null/blank indicate an offline device.
    - Use Tailwind styling similar to `device-info.blade.php`.

- **Approach**:
  - Create a new Livewire component: `php artisan make:livewire OpenServers`.
  - Query the `devices` table for counts per client.
  - Trigger `devices:fetch` on render.
  - Display a responsive grid using Tailwind CSS.
  - Add the route to `routes/web.php`.

### Solution

#### 1. Create the OpenServers Component

Run the following command to generate the component and view:

```bash
./vendor/bin/sail artisan make:livewire OpenServers
```

This creates:
- `app/Http/Livewire/OpenServers.php`
- `resources/views/livewire/open-servers.blade.php`

#### 2. Update the OpenServers Component

Edit `app/Http/Livewire/OpenServers.php` to fetch client data, calculate counts, and refresh devices on render:

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Models\Client;
use App\Models\Device;
use Carbon\Carbon;

class OpenServers extends Component
{
    public $clientsData = [];

    public function mount()
    {
        Log::info('OpenServers mount called');
        $this->refreshDevices();
    }

    public function refreshDevices()
    {
        Log::info('OpenServers refreshDevices called');
        try {
            Artisan::call('devices:fetch');
            Log::info('Devices refreshed for all clients');
        } catch (\Exception $e) {
            Log::error('Error refreshing devices', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        Log::info('OpenServers render called');
        $this->refreshDevices(); // Refresh data on every render

        $this->clientsData = Client::pluck('name')->map(function ($client) {
            $tenMinutesAgo = Carbon::now()->subMinutes(10)->timestamp * 1000; // Convert to milliseconds
            $devices = Device::where('client', $client);
            
            return [
                'client' => $client,
                'devices_count' => $devices->count(),
                'warnings' => $devices->where(function ($query) use ($tenMinutesAgo) {
                    $query->where('unixepoch', '<', $tenMinutesAgo)->orWhereNull('unixepoch');
                })->count(),
                'offline' => $devices->where(function ($query) {
                    $query->whereIn('oopsscreen', ['true', 'N/A'])->orWhereNull('oopsscreen');
                })->count(),
            ];
        })->toArray();

        return view('livewire.open-servers')
            ->layout('layouts.app');
    }
}
```

**Key Features**:
- **Mount**: Calls `refreshDevices()` on component initialization.
- **refreshDevices**: Runs `devices:fetch` to update all clients’ data.
- **Render**:
  - Calls `refreshDevices()` to ensure fresh data.
  - Queries `devices` table for each client to calculate:
    - `devices_count`: Total devices (`count()`).
    - `warnings`: Devices with `unixepoch` older than 10 minutes or null (`Carbon::now()->subMinutes(10)->timestamp * 1000` for milliseconds).
    - `offline`: Devices with `oopsscreen` as `"true"`, `"N/A"`, or null.
  - Uses `Client::pluck('name')` to get all clients.
  - Returns `clientsData` as an array for the view.
- **Logging**: Tracks mount, render, and errors for debugging.
- **Layout**: Uses `layouts.app` for consistency with `DeviceInfo`.

#### 3. Create the OpenServers View

Edit `resources/views/livewire/open-servers.blade.php` to display a responsive grid with Tailwind CSS:

```php
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Open Servers</h1>

    <div class="relative">
        <!-- Loading Indicator Overlay -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center bg-gray-100 bg-opacity-75 z-10">
            <svg class="animate-spin h-8 w-8 text-indigo-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-gray-600 text-base font-medium">Loading servers...</span>
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

        @if (empty($clientsData))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">No clients available.</span>
            </div>
        @else
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Client Servers Status</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Client
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Devices Count
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Warnings
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Offline
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($clientsData as $client)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                        {{ $client['client'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $client['devices_count'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $client['warnings'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $client['offline'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
```

**Key Features**:
- **Styling**: Matches `device-info.blade.php` with Tailwind CSS (`text-gray-900 font-bold` for client names, `bg-white shadow` for table).
- **Grid**: Displays `Client`, `Devices Count`, `Warnings`, `Offline` in a responsive table.
- **Loading Indicator**: Shows a spinner during data refresh (`wire:loading`).
- **Empty State**: Displays a message if no clients are available.
- **Layout**: Uses `layouts.app` for consistency.

#### 4. Add Route for OpenServers

Update `routes/web.php` to add a route for the new component:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\DeviceInfo;
use App\Http\Livewire\OpenServers;

Route::get('/device-info', DeviceInfo::class)->name('device-info');
Route::get('/open-servers', OpenServers::class)->name('open-servers');
```

#### 5. Verify Existing Setup

Ensure the following are in place (from previous fixes):

- **`.env`**:
  ```env
  DEVICE_API_URL=https://mg50v8oyx1.execute-api.us-east-1.amazonaws.com/Prod/data
  ```
  Run: `./vendor/bin/sail artisan config:cache`

- **Device Model** (`app/Models/Device.php`):
  ```php
  <?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Model;

  class Device extends Model
  {
      protected $fillable = [
          'client',
          'operatingSystem',
          'macAddress',
          'model',
          'firmwareVersion',
          'screenshot',
          'oopsscreen',
          'lastreboot',
          'unixepoch',
      ];

      protected $casts = [
          'lastreboot' => 'datetime',
      ];
  }
  ```

- **FetchDeviceData Command** (`app/Console/Commands/FetchDeviceData.php`):
  - Ensure it handles `oopsscreen` as a string and supports `--client`.
  - From your latest version:
    ```php
    'oopsscreen' => $device['oopsscreen'] ?? null,
    ```

- **Scheduler**:
  - Assuming you’re using the while loop in Sail:
    ```bash
    ./vendor/bin/sail shell
    while true; do php artisan schedule:run; sleep 60; done
    ```

#### 6. Test the OpenServers Component

1. **Run Migration** (if not already done):
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

2. **Test FetchDeviceData**:
   ```bash
   ./vendor/bin/sail artisan devices:fetch
   ```
   Check `storage/logs/laravel.log` for:
   ```
   [2025-09-16 10:55:00] local.INFO: FetchDeviceData command started {"clients":["ta","dev1"]}
   [2025-09-16 10:55:00] local.INFO: Devices stored for client {"client":"dev1","count":50}
   ```

3. **Test UI**:
   - Visit `/open-servers` in the browser.
   - Verify the grid shows:
     - `Client`: Names like "ta", "dev1".
     - `Devices Count`: Total devices per client.
     - `Warnings`: Count of devices with `unixepoch < now - 10 minutes` or null.
     - `Offline`: Count of devices with `oopsscreen` as `"true"`, `"N/A"`, or null.
   - Check that the data refreshes on page load (loading spinner appears).
   - Monitor browser memory (Chrome DevTools: F12 > Performance > Memory) to ensure low usage (<10MB).

4. **Verify Database**:
   ```bash
   ./vendor/bin/sail artisan tinker
   >>> App\Models\Device::where('client', 'dev1')->count()
   >>> App\Models\Device::where('client', 'dev1')->whereIn('oopsscreen', ['true', 'N/A'])->orWhereNull('oopsscreen')->count()
   >>> App\Models\Device::where('client', 'dev1')->where('unixepoch', '<', Carbon\Carbon::now()->subMinutes(10)->timestamp * 1000)->orWhereNull('unixepoch')->count()
   ```

5. **Test Scheduler**:
   - Ensure the while loop is running:
     ```bash
     ps aux | grep schedule:run
     ```
   - Wait 5 minutes and check logs for `FetchDeviceData command started`.

#### 7. Performance Considerations

- **Server-Side Processing**: The component queries counts directly from the database, avoiding client-side processing of large datasets.
- **Refresh Overhead**: Calling `devices:fetch` on every render may be heavy for many clients. Consider caching counts or limiting refreshes:
  ```php
  public function render()
  {
      if (! Cache::has('open_servers_last_refresh') || Cache::get('open_servers_last_refresh')->diffInMinutes(now()) >= 5) {
          $this->refreshDevices();
          Cache::put('open_servers_last_refresh', now(), now()->addMinutes(10));
      }
      // ... rest of render
  }
  ```
  - This limits refreshes to every 5 minutes, syncing with the scheduler.

- **Query Optimization**: Indexes on `client`, `unixepoch`, and `oopsscreen` (`macAddress` already indexed) ensure fast queries:
  ```php
  $table->index(['client', 'unixepoch', 'oopsscreen']);
  ```
  - Add this to the migration if needed and re-run:
    ```bash
    ./vendor/bin/sail artisan migrate:rollback --step=1
    ./vendor/bin/sail artisan migrate
    ```

#### 8. Deployment Notes (Sail/Containers)

- **Sail Scheduler**: Continue using the while loop (`while true; do php artisan schedule:run; sleep 60; done`) for development.
- **Production**:
  - Use a separate scheduler container or cron in the Docker image (as outlined in the previous response).
  - Example `docker-compose.yml` for scheduler:
    ```yaml
    scheduler:
      build:
        context: ./vendor/laravel/sail/runtimes/8.2
        dockerfile: Dockerfile
      image: sail-8.2/app
      container_name: sail-scheduler
      volumes:
        - '.:/var/www/html'
      environment:
        - DEVICE_API_URL=https://mg50v8oyx1.execute-api.us-east-1.amazonaws.com/Prod/data
      command: /bin/bash -c "while true; do php artisan schedule:run; sleep 60; done"
    ```

#### Troubleshooting

- **No Data in Grid**:
  - Check `clients` table: `App\Models\Client::count()`.
  - Verify API: `curl "https://mg50v8oyx1.execute-api.us-east-1.amazonaws.com/Prod/data?client=dev1"`.
  - Ensure `.env`: `DEVICE_API_URL=https://mg50v8oyx1.execute-api.us-east-1.amazonaws.com/Prod/data`.

- **Errors in Logs**:
  - Share `storage/logs/laravel.log` entries after visiting `/open-servers`.
  - Look for `OpenServers render called` or `Error refreshing devices`.

- **Performance Issues**:
  - Monitor query performance: `DB::enableQueryLog(); DB::getQueryLog()` in Tinker.
  - Check browser memory (Chrome DevTools > Performance > Memory).

- **Scheduler Issues**:
  - Verify: `ps aux | grep schedule:run`.
  - Restart: `./vendor/bin/sail shell` and rerun the while loop.

Test by visiting `/open-servers`, checking the grid, and confirming data refreshes. If issues arise, share logs, client count, or API response samples for further assistance!