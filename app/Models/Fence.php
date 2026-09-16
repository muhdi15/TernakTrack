<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fence extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'farm_id',
        'name',
        'description',
        'color',
        'fence_type',
        'polygon_coordinates',
        'area_hectares',
        'version',
        'is_active',
        'alert_on_exit',
        'alert_on_enter',
    ];

    protected function casts(): array
    {
        return [
            'polygon_coordinates' => 'array',
            'area_hectares' => 'decimal:4',
            'version' => 'integer',
            'is_active' => 'boolean',
            'alert_on_exit' => 'boolean',
            'alert_on_enter' => 'boolean',
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

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'animal_fence')
            ->withPivot('assigned_at');
    }

    public function geofenceEvents()
    {
        return $this->hasMany(GeofenceEvent::class);
    }

    public function calibrationSessions()
    {
        return $this->hasMany(CalibrationSession::class);
    }
}
