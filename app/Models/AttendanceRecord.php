<?php

namespace App\Models;

use App\Casts\KolkataDateTime;
use App\Casts\UtcDateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'employee_shift_assignment_id',
        'work_date',
        'scheduled_start_at',
        'scheduled_end_at',
        'late_after_at',
        'check_in_at',
        'check_out_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'scheduled_start_at' => KolkataDateTime::class,
            'scheduled_end_at' => KolkataDateTime::class,
            'late_after_at' => KolkataDateTime::class,
            'check_in_at' => UtcDateTime::class,
            'check_out_at' => UtcDateTime::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeShiftAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeShiftAssignment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class);
    }
}
