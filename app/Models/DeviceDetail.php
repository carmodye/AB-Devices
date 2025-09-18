<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceDetail extends Model
{
    protected $fillable = [
        'macAddress',
        'display_id',
        'display_name',
        'device_id',
        'device_name',
        'device_version',
        'site_id',
        'site_name',
        'app_geometry',
        'app_id',
        'app_name',
        'app_package',
        'app_version',
        'ip_address',
        'user_id',
        'user_name',
        'display_info',
    ];

    protected $casts = [
        'display_info' => 'array', // Cast JSON to array
    ];

    public function device()
    {
        return $this->hasOne(Device::class, 'macAddress', 'macAddress');
    }
}