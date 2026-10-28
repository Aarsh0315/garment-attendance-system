<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'ends_next_day',
        'late_grace_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ends_next_day' => 'boolean',
            'late_grace_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }
}
