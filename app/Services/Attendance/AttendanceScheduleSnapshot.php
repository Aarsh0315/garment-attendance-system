<?php

namespace App\Services\Attendance;

use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Support\AttendanceClock;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class AttendanceScheduleSnapshot
{
    private function __construct(
        public CarbonImmutable $workDate,
        public CarbonImmutable $scheduledStartAt,
        public CarbonImmutable $scheduledEndAt,
        public CarbonImmutable $lateAfterAt,
    ) {}

    /**
     * Build the local schedule snapshot for the assignment covering the server instant's Kolkata work date.
     * The caller must supply a trusted server-generated instant, not a client timestamp.
     */
    public static function fromAssignment(
        EmployeeShiftAssignment $assignment,
        DateTimeInterface $serverGeneratedInstant,
    ): self {
        $shift = $assignment->shift;

        if (! $shift instanceof Shift) {
            throw new InvalidArgumentException('The shift assignment must reference an existing shift.');
        }

        $workDate = AttendanceClock::workDate($serverGeneratedInstant);
        $workDateString = $workDate->toDateString();
        $effectiveFrom = self::dateString($assignment->effective_from);
        $effectiveTo = $assignment->effective_to === null
            ? null
            : self::dateString($assignment->effective_to);

        if ($workDateString < $effectiveFrom || ($effectiveTo !== null && $workDateString > $effectiveTo)) {
            throw new InvalidArgumentException('The shift assignment does not cover the work date.');
        }

        $timezone = AttendanceClock::LOCAL_TIMEZONE;
        $scheduledStartAt = AttendanceClock::parseInTimezone(
            $workDateString.' '.$shift->start_time,
            $timezone,
        );
        $scheduledEndDate = $shift->ends_next_day ? $workDate->addDay() : $workDate;
        $scheduledEndAt = AttendanceClock::parseInTimezone(
            $scheduledEndDate->toDateString().' '.$shift->end_time,
            $timezone,
        );

        return new self(
            workDate: $workDate,
            scheduledStartAt: $scheduledStartAt,
            scheduledEndAt: $scheduledEndAt,
            lateAfterAt: $scheduledStartAt->addMinutes((int) $shift->late_grace_minutes),
        );
    }

    public function isLate(DateTimeInterface $actualPunchInstant): bool
    {
        return AttendanceClock::toUtc($actualPunchInstant)
            ->greaterThan(AttendanceClock::toUtc($this->lateAfterAt));
    }

    private static function dateString(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return substr($value, 0, 10);
        }

        throw new InvalidArgumentException('The shift assignment must have valid effective dates.');
    }
}
