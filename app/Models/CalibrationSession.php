<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CalibrationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fence_id',
        'session_token',
        'name',
        'points',
        'status',
        'expires_at',
        'completed_at',
        'device_info',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'device_info' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CalibrationSession $session) {
            if (empty($session->session_token)) {
                $session->session_token = Str::random(64);
            }
            if (empty($session->points)) {
                $session->points = [];
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fence()
    {
        return $this->belongsTo(Fence::class);
    }
}
