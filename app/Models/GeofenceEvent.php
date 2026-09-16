<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeofenceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'fence_id',
        'device_id',
        'event_type',
        'latitude',
        'longitude',
        'distance_from_fence_meters',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_from_fence_meters' => 'decimal:2',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function fence()
    {
        return $this->belongsTo(Fence::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function alert()
    {
        return $this->hasOne(Alert::class);
    }
}
