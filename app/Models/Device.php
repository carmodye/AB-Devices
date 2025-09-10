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
        'oopsscreen' => 'boolean',
        'lastreboot' => 'datetime',
    ];
}