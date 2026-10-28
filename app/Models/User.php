<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_HR_ADMIN = 'hr_admin';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_EMPLOYEE = 'employee';

    public const ROLES = [
        self::ROLE_HR_ADMIN,
        self::ROLE_SUPERVISOR,
        self::ROLE_EMPLOYEE,
    ];

    public function roleDashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_HR_ADMIN => 'dashboards.hr-admin',
            self::ROLE_SUPERVISOR => 'dashboards.supervisor',
            self::ROLE_EMPLOYEE => 'dashboards.employee',
            default => abort(403),
        };
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(EmployeeSupervisorAssignment::class, 'supervisor_user_id');
    }

    public function assignedShiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class, 'assigned_by_user_id');
    }

    public function attendanceEvents(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class, 'actor_user_id');
    }

    public function attendanceAuditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class, 'actor_user_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }
}
