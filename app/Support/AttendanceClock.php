<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class AttendanceClock
{
    public const LOCAL_TIMEZONE = 'Asia/Kolkata';

    public const STORAGE_TIMEZONE = 'UTC';

    public static function parseInTimezone(string|DateTimeInterface $value, string $timezone): CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone($timezone);
        }

        return CarbonImmutable::parse($value, $timezone)->setTimezone($timezone);
    }

    public static function toUtc(DateTimeInterface $instant): CarbonImmutable
    {
        return CarbonImmutable::instance($instant)->setTimezone(self::STORAGE_TIMEZONE);
    }

    public static function toLocalDisplay(DateTimeInterface $utcInstant): CarbonImmutable
    {
        return self::toUtc($utcInstant)->setTimezone(self::LOCAL_TIMEZONE);
    }

    public static function workDate(DateTimeInterface $serverGeneratedInstant): CarbonImmutable
    {
        return self::toLocalDisplay($serverGeneratedInstant)->startOfDay();
    }
}
