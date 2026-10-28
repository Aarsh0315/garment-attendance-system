<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\OfficeLocation;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\AttendancePunchService;
use App\Services\EmployeeShiftAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AttendancePunchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_check_in_creates_a_record_with_local_schedule_and_an_accepted_utc_event(): void
    {
        [$employee, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');

        $record = $this->service()->checkIn($user, 0, 0, 10);
        $event = $record->events()->firstOrFail();

        $this->assertSame('2026-10-28', $record->work_date->toDateString());
        $this->assertSame('2026-10-28 10:00:00', $record->scheduled_start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-28 19:00:00', $record->scheduled_end_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-28 10:15:00', $record->late_after_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-28 04:30:00', $record->check_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $record->fresh()->check_in_at->getTimezone()->getName());
        $this->assertSame('2026-10-28 04:30:00', $event->event_at->format('Y-m-d H:i:s'));
        $this->assertSame('check_in', $event->event_type);
        $this->assertSame('accepted', $event->outcome);
        $this->assertSame('employee_web', $event->source);
        $this->assertNotNull($event->office_location_id);
        $this->assertSame('0.0000000', $event->latitude);
        $this->assertSame('0.0000000', $event->longitude);
        $this->assertSame('10.00', $event->accuracy_meters);
        $this->assertSame('0.00', $event->distance_meters);
        $this->assertSame('accepted', $event->outcome);
        $this->assertSame('employee_web', $event->source);
        $this->assertSame($record->id, $event->attendance_record_id);
        $this->assertSame($employee->id, $event->employee_id);
        $this->assertNull($this->service()->elapsedSeconds($record));
        $this->assertFalse($this->service()->isLate($record));
    }

    public function test_duplicate_check_in_is_rejected_without_an_extra_record_or_event(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');
        $this->service()->checkIn($user, 0, 0, 10);

        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('A second check-in for the same work date should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseCount('attendance_events', 1);
    }

    public function test_missing_or_inactive_employee_is_rejected(): void
    {
        $missing = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        try {
            $this->service()->checkIn($missing, 0, 0, 10);
            $this->fail('A missing employee profile should not be able to check in.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('employee', $exception->errors());
            $this->assertDatabaseCount('attendance_records', 0);
        }

        [$employee, $user] = $this->createAttendanceSetup();
        $user->is_active = false;
        $user->save();

        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('An inactive employee account should not be able to check in.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('employee', $exception->errors());
        }

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_missing_shift_assignment_is_rejected_without_writes(): void
    {
        [, $user] = $this->createAttendanceSetup(withAssignment: false);
        $this->freezeAt('2026-10-28T04:30:00Z');

        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('An employee without an assignment for this work date should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('work_date', $exception->errors());
        }

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_work_date_uses_kolkata_midnight_and_assignment_dates_inclusively(): void
    {
        [, $user] = $this->createAttendanceSetup(effectiveFrom: '2026-10-29', effectiveTo: '2026-10-29');
        $this->freezeAt('2026-10-28T18:30:00Z');

        $record = $this->service()->checkIn($user, 0, 0, 10);

        $this->assertSame('2026-10-29', $record->work_date->toDateString());
        $this->assertSame('2026-10-29 10:00:00', $record->scheduled_start_at->format('Y-m-d H:i:s'));
    }

    public function test_late_status_is_derived_and_strictly_after_the_saved_threshold(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $service = $this->service();

        $this->freezeAt('2026-10-28T04:45:00Z');
        $atThreshold = $service->checkIn($user, 0, 0, 10);
        $this->assertFalse($service->isLate($atThreshold));

        [, $lateUser] = $this->createAttendanceSetup(employeeCode: 'EMP-002', email: 'late@example.test');
        $this->freezeAt('2026-10-28T04:45:01Z');
        $afterThreshold = $service->checkIn($lateUser, 0, 0, 10);
        $this->assertTrue($service->isLate($afterThreshold));
    }

    public function test_overnight_shift_punches_are_rejected_without_creating_attendance(): void
    {
        [, $user] = $this->createAttendanceSetup(endsNextDay: true);
        $this->freezeAt('2026-10-28T16:30:00Z');

        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('Overnight work-date behavior is not configured for this service.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_check_out_requires_an_open_check_in(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T13:30:00Z');

        try {
            $this->service()->checkOut($user, 0, 0, 10);
            $this->fail('Check-out without a check-in should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_check_out_requires_a_matching_accepted_check_in_event(): void
    {
        [$employee, $user, , $assignment] = $this->createAttendanceSetup();
        $record = new AttendanceRecord;
        $record->employee_id = $employee->id;
        $record->employee_shift_assignment_id = $assignment->id;
        $record->work_date = '2026-10-28';
        $record->scheduled_start_at = '2026-10-28 10:00:00';
        $record->scheduled_end_at = '2026-10-28 19:00:00';
        $record->late_after_at = '2026-10-28 10:15:00';
        $record->check_in_at = CarbonImmutable::parse('2026-10-28T04:30:00Z');
        $record->save();
        $this->freezeAt('2026-10-28T13:30:00Z');

        try {
            $this->service()->checkOut($user, 0, 0, 10);
            $this->fail('A check-out without an accepted check-in event should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertNull($record->fresh()->check_out_at);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_check_out_records_utc_event_and_duration_is_derived_without_storage(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');
        $record = $this->service()->checkIn($user, 0, 0, 10);
        $this->freezeAt('2026-10-28T13:30:00Z');

        $completed = $this->service()->checkOut($user, 0, 0, 10);
        $events = $completed->events()->orderBy('id')->get();

        $this->assertSame($record->id, $completed->id);
        $this->assertSame('2026-10-28 13:30:00', $completed->check_out_at->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $completed->fresh()->check_out_at->getTimezone()->getName());
        $this->assertSame('check_in', $events[0]->event_type);
        $this->assertSame('check_out', $events[1]->event_type);
        $this->assertSame('2026-10-28 13:30:00', $events[1]->event_at->format('Y-m-d H:i:s'));
        $this->assertSame('accepted', $events[1]->outcome);
        $this->assertSame('employee_web', $events[1]->source);
        $this->assertSame($events[0]->office_location_id, $events[1]->office_location_id);
        $this->assertSame('0.0000000', $events[1]->latitude);
        $this->assertSame('0.0000000', $events[1]->longitude);
        $this->assertSame('10.00', $events[1]->accuracy_meters);
        $this->assertSame('0.00', $events[1]->distance_meters);
        $this->assertSame(32400, $this->service()->elapsedSeconds($completed));
        $this->assertFalse($completed->getConnection()->getSchemaBuilder()->hasColumn('attendance_records', 'elapsed_seconds'));
        $this->assertSame(2, $completed->events()->count());
        $this->assertSame($completed->id, $events[0]->attendance_record_id);
        $this->assertSame($completed->id, $events[1]->attendance_record_id);
    }

    public function test_duplicate_check_out_is_rejected_without_changing_the_record_or_events(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');
        $this->service()->checkIn($user, 0, 0, 10);
        $this->freezeAt('2026-10-28T13:30:00Z');
        $record = $this->service()->checkOut($user, 0, 0, 10);
        $checkoutAt = $record->check_out_at->format('Y-m-d H:i:s');

        try {
            $this->service()->checkOut($user, 0, 0, 10);
            $this->fail('A duplicate check-out should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertSame($checkoutAt, $record->fresh()->check_out_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('attendance_events', 2);
    }

    public function test_check_in_rejects_outside_radius_poor_accuracy_and_invalid_coordinates_without_writes(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');

        foreach ([[0.01, 0, 10, 'location'], [0, 0, 101, 'accuracy_meters'], [91, 0, 10, 'latitude']] as [$latitude, $longitude, $accuracy, $field]) {
            try {
                $this->service()->checkIn($user, $latitude, $longitude, $accuracy);
                $this->fail('Invalid GPS data must reject check-in.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }

            $this->assertDatabaseCount('attendance_records', 0);
            $this->assertDatabaseCount('attendance_events', 0);
        }
    }

    public function test_check_in_rejects_missing_location_or_invalid_environment_configuration_without_writes(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');

        OfficeLocation::query()->where('environment', 'development')->update(['is_active' => false]);

        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('An inactive location must reject check-in.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }

        Config::set('attendance.location_environment', 'production');
        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('A missing configured environment must not fall back to development.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }

        Config::set('attendance.location_environment', null);
        try {
            $this->service()->checkIn($user, 0, 0, 10);
            $this->fail('Missing environment configuration must reject check-in.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('attendance_records', 0);
        }

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_rejected_check_out_does_not_update_the_record_or_add_an_event(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');
        $record = $this->service()->checkIn($user, 0, 0, 10);
        $this->freezeAt('2026-10-28T13:30:00Z');

        try {
            $this->service()->checkOut($user, 0.01, 0, 10);
            $this->fail('An outside-radius check-out must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('location', $exception->errors());
        }

        $this->assertNull($record->fresh()->check_out_at);
        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseCount('attendance_events', 1);
    }

    public function test_check_out_before_check_in_is_rejected_without_an_event(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');
        $record = $this->service()->checkIn($user, 0, 0, 10);
        $this->freezeAt('2026-10-28T04:29:59Z');

        try {
            $this->service()->checkOut($user, 0, 0, 10);
            $this->fail('A check-out before check-in should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }

        $this->assertNull($record->fresh()->check_out_at);
        $this->assertDatabaseCount('attendance_events', 1);
    }

    public function test_event_write_failure_rolls_back_check_in_and_check_out_changes(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->freezeAt('2026-10-28T04:30:00Z');

        AttendanceEvent::creating(function (AttendanceEvent $event): void {
            if ($event->event_type === 'check_in') {
                throw new RuntimeException('Simulated event insert failure.');
            }
        });

        try {
            try {
                $this->service()->checkIn($user, 0, 0, 10);
                $this->fail('Check-in should fail when its event cannot be written.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Simulated event insert failure.', $exception->getMessage());
            }
        } finally {
            AttendanceEvent::flushEventListeners();
        }

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);

        $record = $this->service()->checkIn($user, 0, 0, 10);
        $this->freezeAt('2026-10-28T13:30:00Z');
        AttendanceEvent::creating(function (AttendanceEvent $event): void {
            if ($event->event_type === 'check_out') {
                throw new RuntimeException('Simulated event insert failure.');
            }
        });

        try {
            try {
                $this->service()->checkOut($user, 0, 0, 10);
                $this->fail('Check-out should fail when its event cannot be written.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Simulated event insert failure.', $exception->getMessage());
            }
        } finally {
            AttendanceEvent::flushEventListeners();
        }

        $this->assertNull($record->fresh()->check_out_at);
        $this->assertDatabaseCount('attendance_events', 1);
    }

    private function service(): AttendancePunchService
    {
        return app(AttendancePunchService::class);
    }

    private function freezeAt(string $instant): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($instant));
    }

    /** @return array{Employee, User, Shift, EmployeeShiftAssignment|null} */
    private function createAttendanceSetup(
        bool $withAssignment = true,
        bool $endsNextDay = false,
        string $employeeCode = 'EMP-001',
        string $email = 'employee@example.test',
        string $effectiveFrom = '2026-01-01',
        ?string $effectiveTo = null,
    ): array {
        Config::set('attendance.location_environment', 'development');
        if (! OfficeLocation::query()->where('environment', 'development')->where('is_active', true)->exists()) {
            OfficeLocation::create([
                'name' => 'Test Attendance Location',
                'environment' => 'development',
                'latitude' => '0.0000000',
                'longitude' => '0.0000000',
                'radius_meters' => 100,
                'is_active' => true,
            ]);
        }

        $department = Department::create(['name' => 'Stitching '.$employeeCode]);
        $user = User::factory()->create([
            'name' => 'Test Employee',
            'email' => $email,
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => $employeeCode,
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_HR_ADMIN,
            'is_active' => true,
        ]);
        $shift = Shift::create([
            'name' => 'Test Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'ends_next_day' => $endsNextDay,
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);
        $assignment = $withAssignment
            ? app(EmployeeShiftAssignmentService::class)->assign(
                $employee,
                $shift,
                $effectiveFrom,
                $effectiveTo,
                $admin,
            )
            : null;

        return [$employee, $user, $shift, $assignment];
    }
}
