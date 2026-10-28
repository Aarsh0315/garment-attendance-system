<?php

namespace App\Services\Attendance;

use InvalidArgumentException;

final class AttendanceLocationEnvironmentResolver
{
    /** Return the explicitly configured office-location label without transforming it. */
    public function resolve(): string
    {
        $environment = config('attendance.location_environment');

        if (
            ! is_string($environment)
            || trim($environment) === ''
            || trim($environment) !== $environment
            || mb_strlen($environment, 'UTF-8') > 16
        ) {
            throw new InvalidArgumentException(
                'The attendance location environment must be configured as an exact label of at most 16 characters.'
            );
        }

        return $environment;
    }
}
