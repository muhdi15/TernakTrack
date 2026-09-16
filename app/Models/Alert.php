<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animal_id',
        'device_id',
        'geofence_event_id',
        'type',
        'severity',
        'title',
        'message',
        'channels_sent',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'channels_sent' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function geofenceEvent()
    {
        return $this->belongsTo(GeofenceEvent::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
