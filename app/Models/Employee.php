<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'department_id',
        'designation',
        'joining_date',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Employee $employee): void {
            $employee->employee_code = mb_strtoupper(trim($employee->employee_code), 'UTF-8');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(EmployeeSupervisorAssignment::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceEvents(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class);
    }
}
