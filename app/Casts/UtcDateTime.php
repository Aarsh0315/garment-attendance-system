<?php

namespace App\Casts;

use App\Support\AttendanceClock;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class UtcDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        return AttendanceClock::parseInTimezone((string) $value, AttendanceClock::STORAGE_TIMEZONE);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! $value instanceof DateTimeInterface) {
            throw new InvalidArgumentException("The {$key} value must be a datetime string or DateTimeInterface.");
        }

        return AttendanceClock::parseInTimezone($value, AttendanceClock::STORAGE_TIMEZONE)->format('Y-m-d H:i:s');
    }
}
