<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class FetchDeviceDetails extends Command
{
    protected $signature = 'device-details:fetch {--client=}';

    protected $description = 'Fetch detailed device data for all clients or a specific client and store in Redis cache';

    public function handle()
    {
        $client = $this->option('client');
        $clients = $client ? [$client] : Client::pluck('name')->toArray();
        Log::info('FetchDeviceDetails command started', ['clients' => $clients]);

        $ttl = 3600; // 60 minutes in seconds

        foreach ($clients as $client) {
            try {
                $url = str_replace('{client}', $client, env('DEVICE_DETAILS_API_URL'));
                if (!$url) {
                    Log::error('DEVICE_DETAILS_API_URL not set', ['client' => $client]);
                    throw new \Exception('API URL not configured');
                }
                $response = Http::timeout(10)->get($url);
                Log::info('API Response', [
                    'client' => $client,
                    'url' => $url,
                    'status' => $response->status(),
                    'body_size' => strlen($response->body()) / 1024 / 1024 . 'MB',
                    'device_count' => is_array($response->json()['devices'] ?? []) ? count($response->json()['devices']) : 0,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    $devices = $data['devices'] ?? [];

                    $redis = Redis::connection('cache');
                    $detailsByMac = []; // Key by macaddress for easy lookup
                    foreach ($devices as $device) {
                        $macAddress = strtoupper($device['device_macaddress'] ?? ''); // Normalize to uppercase
                        if (empty($macAddress)) {
                            Log::warning('Skipping device without macaddress', ['client' => $client, 'device' => $device]);
                            continue;
                        }
                        $detailsByMac[$macAddress] = $device;
                    }

                    // Store as JSON keyed by client
                    $clientKey = "device_details:{$client}";
                    $redis->set($clientKey, json_encode($detailsByMac), 'EX', $ttl);
                    // Merge with data
                    $devicesKey = "devices:{$client}";
                    $devicesRaw = $redis->get($devicesKey);
                    $devices = $devicesRaw ? json_decode($devicesRaw, true) : [];
                    $merged = [];
                    foreach ($devices as $device) {
                        $mac = strtoupper(trim($device['macAddress'] ?? ''));
                        $detail = $detailsByMac[$mac] ?? [];
                        $mergedDevice = $device;
                        $mergedDevice['display_name'] = $detail['display_name'] ?? null;
                        $mergedDevice['device_version'] = $detail['device_version'] ?? null;
                        $mergedDevice['site_name'] = $detail['site_name'] ?? null;
                        $merged[] = $mergedDevice;
                    }
                    $combinedKey = "combined_devices:{$client}";
                    $redis->set($combinedKey, json_encode($merged), 'EX', $ttl);
                    Log::info('Combined devices stored', ['client' => $client, 'count' => count($merged)]);

                    // Cache last API call
                    \Illuminate\Support\Facades\Cache::put('device_details_' . $client . '_last_api_call', now()->toDateTimeString(), now()->addMinutes(10));
                    Log::info('Device details stored for client in Redis', ['client' => $client, 'bare_key' => $clientKey, 'stored_count' => count($detailsByMac)]);
                } else {
                    Log::error('API request failed', ['client' => $client, 'status' => $response->status()]);
                    throw new \Exception('API request failed with status: ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching device details for client', [
                    'client' => $client,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Unified last refresh time
        \Illuminate\Support\Facades\Cache::put('device_details_all_last_api_call', now()->toDateTimeString(), now()->addMinutes(10));
        Log::info('FetchDeviceDetails command completed', ['last_refresh' => \Illuminate\Support\Facades\Cache::get('device_details_all_last_api_call')]);
    }
}
