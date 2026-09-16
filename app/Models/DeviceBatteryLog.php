<?php

namespace App\Models;

use Database\Factories\DeviceBatteryLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceBatteryLog extends Model
{
    /** @use HasFactory<DeviceBatteryLogFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'battery_level',
        'battery_voltage',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'battery_level' => 'integer',
            'battery_voltage' => 'decimal:3',
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
