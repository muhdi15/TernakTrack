<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'record_date',
        'type',
        'description',
        'vet_name',
        'next_due_date',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'next_due_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
