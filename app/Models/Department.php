<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Department $department): void {
            $department->name = trim($department->name);
            $department->normalized_name = mb_strtolower($department->name, 'UTF-8');
        });
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
