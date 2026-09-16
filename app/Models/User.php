<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'telegram_chat_id',
        'notification_preferences',
        'avatar_path',
        'timezone',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPeternak(): bool
    {
        return $this->role === 'peternak';
    }

    public function farms()
    {
        return $this->hasMany(Farm::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function fences()
    {
        return $this->hasMany(Fence::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function calibrationSessions()
    {
        return $this->hasMany(CalibrationSession::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
