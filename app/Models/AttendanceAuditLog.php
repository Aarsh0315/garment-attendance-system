<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AttendanceAuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    // Audit entries are server-controlled; trusted code should assign attributes explicitly before save().
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Attendance audit entries are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Attendance audit entries are append-only.');
        });
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
