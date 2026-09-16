<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'animal_id',
        'latitude',
        'longitude',
        'altitude',
        'speed_kmh',
        'heading',
        'accuracy_meters',
        'satellites',
        'hdop',
        'recorded_at',
        'received_at',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'altitude' => 'decimal:2',
            'speed_kmh' => 'decimal:2',
            'heading' => 'decimal:2',
            'accuracy_meters' => 'decimal:2',
            'satellites' => 'integer',
            'hdop' => 'decimal:2',
            'recorded_at' => 'datetime',
            'received_at' => 'datetime',
            'is_valid' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
