<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;

class FetchDeviceData extends Command
{
    protected $signature = 'devices:fetch';
    protected $description = 'Fetch device data for all clients and cache results';

    public function handle()
    {
        $clients = Client::pluck('name')->toArray();
        Log::info('FetchDeviceData command started', ['clients' => $clients]);

        foreach ($clients as $client) {
            try {
                $cacheKey = 'devices_' . $client;
                $cacheTTL = now()->addMinutes(10);

                $devices = Cache::remember($cacheKey, $cacheTTL, function () use ($client, $cacheKey, $cacheTTL) {
                    $url = env('DEVICE_API_URL');
                    if (!$url) {
                        Log::error('DEVICE_API_URL not set', ['client' => $client]);
                        throw new \Exception('API URL not configured');
                    }
                    $response = Http::timeout(10)->get($url, [
                        'client' => $client
                    ]);
                    Log::info('API Response', [
                        'client' => $client,
                        'url' => $url . '?client=' . $client,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        $result = collect(is_array($data) ? $data : []);
                        Cache::put($cacheKey . '_last_api_call', now()->toDateTimeString(), $cacheTTL);
                        return $result;
                    }
                    Log::error('API request failed', ['client' => $client, 'status' => $response->status()]);
                    throw new \Exception('API request failed with status: ' . $response->status());
                });

                Log::info('Devices cached for client', ['client' => $client, 'count' => $devices->count()]);
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