<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;
use App\Models\Device;
use App\Models\DeviceDetail;

class FetchDeviceDetails extends Command
{
    protected $signature = 'devices:fetch-details {--client=}';
    protected $description = 'Fetch device details from CMS API and store in database';

    public function handle()
    {
        $client = $this->option('client');
        $clients = $client ? [$client] : Client::pluck('name')->toArray();
        Log::info('FetchDeviceDetails command started', ['clients' => $clients]);

        foreach ($clients as $client) {
            try {
                $url = "https://{$client}.cms.ab-net.us/api/dumpdata";
                $response = Http::timeout(10)->get($url);
                Log::info('API Response', [
                    'client' => $client,
                    'url' => $url,
                    'status' => $response->status(),
                    'body_size' => strlen($response->body()) / 1024 / 1024 . 'MB',
                    'device_count' => is_array($response->json('devices')) ? count($response->json('devices')) : 0
                ]);

                if ($response->successful()) {
                    $devices = $response->json('devices', []);

                    // Clear existing details for the client's devices
                    DeviceDetail::whereIn('macAddress', Device::where('client', $client)->pluck('macAddress'))->delete();

                    // Insert new details
                    foreach ($devices as $device) {
                        DeviceDetail::create([
                            'macAddress' => $device['device_macaddress'] ?? null,
                            'display_id' => $device['display_id'] ?? null,
                            'display_name' => $device['display_name'] ?? null,
                            'device_id' => $device['device_id'] ?? null,
                            'device_name' => $device['device_name'] ?? null,
                            'device_version' => $device['device_version'] ?? null,
                            'site_id' => $device['site_id'] ?? null,
                            'site_name' => $device['site_name'] ?? null,
                            'app_geometry' => $device['app_geometry'] ?? null,
                            'app_id' => $device['app_id'] ?? null,
                            'app_name' => $device['app_name'] ?? null,
                            'app_package' => $device['app_package'] ?? null,
                            'app_version' => $device['app_version'] ?? null,
                            'ip_address' => $device['ip_address'] ?? null,
                            'user_id' => $device['user_id'] ?? null,
                            'user_name' => $device['user_name'] ?? null,
                            'display_info' => isset($device['display_info']) ? json_encode($device['display_info']) : null,
                        ]);
                    }

                    Cache::put('device_details_' . $client . '_last_api_call', now()->toDateTimeString(), now()->addMinutes(10));
                    Log::info('Device details stored for client', ['client' => $client, 'count' => count($devices)]);
                } else {
                    Log::error('API request failed', ['client' => $client, 'status' => $response->status()]);
                    throw new \Exception('API request failed with status: ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Error fetching device details for client', [
                    'client' => $client,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('FetchDeviceDetails command completed');
    }
}