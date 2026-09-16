<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'farm_id',
        'name',
        'device_code',
        'api_token',
        'status',
        'battery_level',
        'battery_voltage',
        'last_seen_at',
        'firmware_version',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'battery_level' => 'integer',
            'battery_voltage' => 'decimal:3',
            'last_seen_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal()
    {
        return $this->hasOne(Animal::class);
    }

    public function locationLogs()
    {
        return $this->hasMany(LocationLog::class);
    }

    public function geofenceEvents()
    {
        return $this->hasMany(GeofenceEvent::class);
    }

    public function deviceCommands()
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function batteryLogs()
    {
        return $this->hasMany(DeviceBatteryLog::class);
    }

    /**
     * Perangkat dianggap online jika melapor dalam 5 menit terakhir.
     */
    public function isOnline(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThanOrEqualTo(now()->subMinutes(5));
    }

    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(15));
    }
}
