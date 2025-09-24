<?php

namespace App\Console\Commands;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class FetchDeviceData extends Command
{
    protected $signature = 'devices:fetch {--client=}';

    protected $description = 'Fetch device data for all clients or a specific client and store in Redis cache';

    public function handle()
    {
        $client = $this->option('client');
        $clients = $client ? [$client] : Client::pluck('name')->toArray();
        Log::info('FetchDeviceData command started', ['clients' => $clients]);

        $warningThreshold = env('WARNING_THRESHOLD_MINUTES', 10) * 60 * 1000; // Convert to milliseconds
        $errorThreshold = env('ERROR_THRESHOLD_MINUTES', 30) * 60 * 1000; // Convert to milliseconds
        $now = now()->timestamp * 1000; // Current time in milliseconds
        $ttl = 3600; // 60 minutes in seconds

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
                    'device_count' => is_array($response->json()) ? count($response->json()) : 0,
                ]);
                if ($response->successful()) {
                    $data = $response->json();

                    $devices = is_array($data) ? $data : [];
                    $redis = Redis::connection('cache');

                    // Prepare data for caching (array per client, overwrites for uniqueness)
                    $cacheData = [];
                    foreach ($devices as $device) {
                        $macAddress = $device['macAddress'] ?? null;
                        if (empty($macAddress)) {
                            Log::warning('Skipping device without macAddress', ['client' => $client, 'device' => $device]);

                            continue;
                        }

                        $unixepoch = $device['unixepoch'] ?? null;
                        $cacheData[] = [
                            'client' => $device['client'] ?? $client,
                            'operatingSystem' => $device['operatingSystem'] ?? null,
                            'macAddress' => $macAddress,
                            'model' => $device['model'] ?? null,
                            'firmwareVersion' => $device['firmwareVersion'] ?? null,
                            'screenshot' => $device['screenshot'] ?? null,
                            'oopsscreen' => $device['oopsscreen'] ?? null,
                            'lastreboot' => isset($device['lastreboot']) ? Carbon::parse($device['lastreboot'])->toISOString() : null, // Store as ISO string
                            'unixepoch' => $unixepoch,
                            'warning' => is_null($unixepoch) || ($now - $unixepoch > $warningThreshold),
                            'error' => is_null($unixepoch) || ($now - $unixepoch > $errorThreshold),
                        ];
                    }

                    // Store array as JSON in single key per client (overwrites, assumes API unique per MAC) - bare key, facade adds prefix once
                    $clientKey = "devices:{$client}";
                    $redis->set($clientKey, json_encode($cacheData), 'EX', $ttl);

                    // Also store last API call via Cache facade
                    \Illuminate\Support\Facades\Cache::put('devices_' . $client . '_last_api_call', now()->toDateTimeString(), now()->addMinutes(10));
                    Log::info('Devices stored for client in Redis', ['client' => $client, 'bare_key' => $clientKey, 'stored_count' => count($cacheData)]);
                } else {
                    Log::error('API request failed', ['client' => $client, 'status' => $response->status()]);
                    throw new \Exception('API request failed with status: ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching devices for client', [
                    'client' => $client,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Set unified last refresh time
        \Illuminate\Support\Facades\Cache::put('devices_all_last_api_call', now()->toDateTimeString(), now()->addMinutes(10));
        Log::info('FetchDeviceData command completed', ['last_refresh' => \Illuminate\Support\Facades\Cache::get('devices_all_last_api_call')]);
    }
}
