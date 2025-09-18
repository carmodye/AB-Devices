<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;
use App\Models\Device;
use Carbon\Carbon;

class FetchDeviceData extends Command
{
    protected $signature = 'devices:fetch {--client=}';
    protected $description = 'Fetch device data for all clients or a specific client and store in database';

    public function handle()
    {
        $client = $this->option('client');
        $clients = $client ? [$client] : Client::pluck('name')->toArray();
        Log::info('FetchDeviceData command started', ['clients' => $clients]);

        $warningThreshold = env('WARNING_THRESHOLD_MINUTES', 10) * 60 * 1000; // Convert to milliseconds
        $errorThreshold = env('ERROR_THRESHOLD_MINUTES', 30) * 60 * 1000; // Convert to milliseconds
        $now = now()->timestamp * 1000; // Current time in milliseconds

        foreach ($clients as $client) {
            try {
                $url = env('DEVICE_API_URL');
                if (!$url) {
                    Log::error('DEVICE_API_URL not set', ['client' => $client]);
                    throw new \Exception('API URL not configured');
                }
                $response = Http::timeout(10)->get($url, ['client' => $client]);
                Log::info('API Response', [
                    'client' => $client,
                    'url' => $url . '?client=' . $client,
                    'status' => $response->status(),
                    'body_size' => strlen($response->body()) / 1024 / 1024 . 'MB',
                    'device_count' => is_array($response->json()) ? count($response->json()) : 0
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $devices = is_array($data) ? $data : [];

                    // Clear existing devices for the client
                    Device::where('client', $client)->delete();

                    // Insert new devices
                    foreach ($devices as $device) {
                        $unixepoch = $device['unixepoch'] ?? null;
                        Device::create([
                            'client' => $device['client'] ?? $client,
                            'operatingSystem' => $device['operatingSystem'] ?? null,
                            'macAddress' => $device['macAddress'] ?? null,
                            'model' => $device['model'] ?? null,
                            'firmwareVersion' => $device['firmwareVersion'] ?? null,
                            'screenshot' => $device['screenshot'] ?? null,
                            'oopsscreen' => $device['oopsscreen'] ?? null,
                            'lastreboot' => isset($device['lastreboot']) ? Carbon::parse($device['lastreboot']) : null,
                            'unixepoch' => $unixepoch,
                            'warning' => is_null($unixepoch) || ($now - $unixepoch > $warningThreshold),
                            'error' => is_null($unixepoch) || ($now - $unixepoch > $errorThreshold),
                        ]);
                    }

                    Cache::put('devices_' . $client . '_last_api_call', now()->toDateTimeString(), now()->addMinutes(10));
                    Log::info('Devices stored for client', ['client' => $client, 'count' => count($devices)]);
                } else {
                    Log::error('API request failed', ['client' => $client, 'status' => $response->status()]);
                    throw new \Exception('API request failed with status: ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching devices for client', [
                    'client' => $client,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('FetchDeviceData command completed');
    }
}