<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'farm_id',
        'device_id',
        'name',
        'tag_number',
        'species',
        'gender',
        'birth_date',
        'weight_kg',
        'photo_path',
        'health_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'weight_kg' => 'decimal:2',
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

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function fences()
    {
        return $this->belongsToMany(Fence::class, 'animal_fence')
            ->withPivot('assigned_at');
    }

    public function locationLogs()
    {
        return $this->hasMany(LocationLog::class);
    }

    public function geofenceEvents()
    {
        return $this->hasMany(GeofenceEvent::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function healthRecords()
    {
        return $this->hasMany(HealthRecord::class);
    }
}
