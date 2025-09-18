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
        'warning',
        'error',
    ];

    protected $casts = [
        'lastreboot' => 'datetime',
        'warning' => 'boolean',
        'error' => 'boolean',
    ];

    public function detail()
    {
        return $this->hasOne(DeviceDetail::class, 'macAddress', 'macAddress');
    }
}