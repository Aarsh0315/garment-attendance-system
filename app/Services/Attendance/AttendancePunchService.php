<?php

namespace App\Services\Attendance;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\EmployeeShiftAssignmentService;
use App\Support\AttendanceClock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class AttendancePunchService
{
    public function __construct(
        private readonly EmployeeShiftAssignmentService $shiftAssignments,
        private readonly AttendanceLocationEnvironmentResolver $locationEnvironment,
        private readonly AttendanceLocationValidator $locationValidator,
    ) {}

    public function checkIn(User $actor, mixed $latitude, mixed $longitude, mixed $accuracyMeters): AttendanceRecord
    {
        return DB::transaction(function () use ($actor, $latitude, $longitude, $accuracyMeters): AttendanceRecord {
            [$lockedEmployee, $lockedActor] = $this->lockActiveEmployee($actor);
            $instant = CarbonImmutable::now(AttendanceClock::STORAGE_TIMEZONE);
            $workDate = AttendanceClock::workDate($instant)->toDateString();
            $assignment = $this->shiftAssignments->forWorkDate($lockedEmployee, $workDate);
            $assignment->loadMissing('shift');

            if ($assignment->shift->ends_next_day) {
                $this->reject('attendance', 'Punches for overnight shifts are not supported until their work-date rule is configured.');
            }

            $existing = AttendanceRecord::query()
                ->where('employee_id', $lockedEmployee->getKey())
                ->whereDate('work_date', $workDate)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $this->reject('attendance', 'An attendance record already exists for this employee and work date.');
            }

            $locationResult = $this->validateLocation($latitude, $longitude, $accuracyMeters);
            $snapshot = AttendanceScheduleSnapshot::fromAssignment($assignment, $instant);
            $record = new AttendanceRecord;
            $record->employee_id = $lockedEmployee->getKey();
            $record->employee_shift_assignment_id = $assignment->getKey();
            $record->work_date = $snapshot->workDate;
            $record->scheduled_start_at = $snapshot->scheduledStartAt;
            $record->scheduled_end_at = $snapshot->scheduledEndAt;
            $record->late_after_at = $snapshot->lateAfterAt;
            $record->check_in_at = $instant;
            $record->save();

            $this->recordEvent($lockedEmployee, $lockedActor, $record, 'check_in', $instant, $locationResult);

            return $record;
        });
    }

    public function checkOut(User $actor, mixed $latitude, mixed $longitude, mixed $accuracyMeters): AttendanceRecord
    {
        return DB::transaction(function () use ($actor, $latitude, $longitude, $accuracyMeters): AttendanceRecord {
            [$lockedEmployee, $lockedActor] = $this->lockActiveEmployee($actor);
            $instant = CarbonImmutable::now(AttendanceClock::STORAGE_TIMEZONE);
            $today = AttendanceClock::workDate($instant)->toDateString();

            $alreadyCheckedOut = AttendanceRecord::query()
                ->where('employee_id', $lockedEmployee->getKey())
                ->whereDate('work_date', $today)
                ->whereNotNull('check_out_at')
                ->exists();

            if ($alreadyCheckedOut) {
                $this->reject('attendance', 'A check-out has already been recorded for this work date.');
            }

            $openRecords = AttendanceRecord::query()
                ->where('employee_id', $lockedEmployee->getKey())
                ->whereNotNull('check_in_at')
                ->whereNull('check_out_at')
                ->orderByDesc('work_date')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->limit(2)
                ->get();

            if ($openRecords->isEmpty()) {
                $this->reject('attendance', 'There is no open attendance check-in to check out.');
            }

            if ($openRecords->count() > 1) {
                $this->reject('attendance', 'More than one open attendance record exists. Ask HR to review the records before checking out.');
            }

            /** @var AttendanceRecord $record */
            $record = $openRecords->firstOrFail();
            $assignment = $record->employeeShiftAssignment()->with('shift')->firstOrFail();

            if ($assignment->shift->ends_next_day) {
                $this->reject('attendance', 'Punches for overnight shifts are not supported until their work-date rule is configured.');
            }

            $checkInInstant = AttendanceClock::toUtc($record->check_in_at);
            $checkInEvent = $record->events()
                ->where('event_type', 'check_in')
                ->where('outcome', 'accepted')
                ->where('actor_user_id', $lockedActor->getKey())
                ->where('source', 'employee_web')
                ->first();

            if (
                $checkInEvent === null
                || AttendanceClock::toUtc($checkInEvent->event_at)->getTimestamp() !== $checkInInstant->getTimestamp()
            ) {
                $this->reject('attendance', 'The attendance record does not have a matching accepted check-in event.');
            }

            if ($instant->lessThan($checkInInstant)) {
                $this->reject('attendance', 'The check-out time cannot be earlier than the check-in time.');
            }

            $locationResult = $this->validateLocation($latitude, $longitude, $accuracyMeters);
            $record->check_out_at = $instant;
            $record->save();

            $this->recordEvent($lockedEmployee, $lockedActor, $record, 'check_out', $instant, $locationResult);

            return $record;
        });
    }

    /** Return null until both accepted punch instants are present. */
    public function elapsedSeconds(AttendanceRecord $record): ?int
    {
        if ($record->check_in_at === null || $record->check_out_at === null) {
            return null;
        }

        $seconds = AttendanceClock::toUtc($record->check_out_at)->getTimestamp()
            - AttendanceClock::toUtc($record->check_in_at)->getTimestamp();

        if ($seconds < 0) {
            throw new InvalidArgumentException('The attendance record has a check-out earlier than its check-in.');
        }

        return $seconds;
    }

    /** Derive late status from saved timestamps; the schema intentionally stores no redundant flag. */
    public function isLate(AttendanceRecord $record): ?bool
    {
        if ($record->check_in_at === null) {
            return null;
        }

        return AttendanceClock::toUtc($record->check_in_at)
            ->greaterThan(AttendanceClock::toUtc($record->late_after_at));
    }

    /** @return array{Employee, User} */
    private function lockActiveEmployee(User $actor): array
    {
        $lockedEmployee = Employee::query()
            ->where('user_id', $actor->getKey())
            ->lockForUpdate()
            ->first();
        $lockedActor = User::query()
            ->whereKey($actor->getKey())
            ->lockForUpdate()
            ->first();

        if (
            $lockedEmployee === null
            || $lockedActor === null
            || $lockedActor->role !== User::ROLE_EMPLOYEE
            || ! $lockedActor->is_active
        ) {
            $this->reject('employee', 'Attendance is available only to an active employee account.');
        }

        return [$lockedEmployee, $lockedActor];
    }

    private function recordEvent(
        Employee $employee,
        User $actor,
        AttendanceRecord $record,
        string $eventType,
        CarbonImmutable $instant,
        array $locationResult,
    ): void {
        $event = new AttendanceEvent;
        $event->employee_id = $employee->getKey();
        $event->attendance_record_id = $record->getKey();
        $event->actor_user_id = $actor->getKey();
        $event->event_type = $eventType;
        $event->outcome = 'accepted';
        $event->source = 'employee_web';
        $event->event_at = $instant;
        $event->office_location_id = $locationResult['office_location']->getKey();
        $event->latitude = $locationResult['latitude'];
        $event->longitude = $locationResult['longitude'];
        $event->accuracy_meters = $locationResult['accuracy_meters'];
        $event->distance_meters = $locationResult['distance_meters'];
        $event->save();
    }

    /** @return array{office_location: OfficeLocation, latitude: float, longitude: float, accuracy_meters: float, distance_meters: float} */
    private function validateLocation(mixed $latitude, mixed $longitude, mixed $accuracyMeters): array
    {
        return $this->locationValidator->validate(
            $latitude,
            $longitude,
            $accuracyMeters,
            $this->locationEnvironment->resolve(),
        );
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
