<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\AttendanceScheduleSnapshot;
use App\Support\AttendanceClock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTimeFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_utc_instants_round_trip_for_event_and_record_punches_and_convert_for_kolkata_display(): void
    {
        [$employee, $assignment] = $this->createAttendanceSetup();
        $snapshot = AttendanceScheduleSnapshot::fromAssignment(
            $assignment,
            CarbonImmutable::parse('2026-10-28T18:30:00Z'),
        );
        $record = new AttendanceRecord;
        $record->employee_id = $employee->id;
        $record->employee_shift_assignment_id = $assignment->id;
        $record->work_date = $snapshot->workDate;
        $record->scheduled_start_at = $snapshot->scheduledStartAt;
        $record->scheduled_end_at = $snapshot->scheduledEndAt;
        $record->late_after_at = $snapshot->lateAfterAt;
        $record->check_in_at = CarbonImmutable::parse('2026-10-29T10:00:00+05:30');
        $record->check_out_at = CarbonImmutable::parse('2026-10-29T19:00:00+05:30');
        $record->save();

        $actor = $employee->user;
        $event = new AttendanceEvent;
        $event->employee_id = $employee->id;
        $event->attendance_record_id = $record->id;
        $event->actor_user_id = $actor->id;
        $event->event_type = 'check_in';
        $event->outcome = 'accepted';
        $event->source = 'employee_web';
        $event->event_at = CarbonImmutable::parse('2026-10-29T10:00:00+05:30');
        $event->save();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'scheduled_start_at' => '2026-10-29 10:00:00',
            'check_in_at' => '2026-10-29 04:30:00',
            'check_out_at' => '2026-10-29 13:30:00',
        ]);
        $this->assertDatabaseHas('attendance_events', [
            'id' => $event->id,
            'event_at' => '2026-10-29 04:30:00',
        ]);

        $storedRecord = $record->fresh();
        $storedEvent = $event->fresh();

        $this->assertSame('UTC', $storedRecord->check_in_at->getTimezone()->getName());
        $this->assertSame(
            AttendanceClock::LOCAL_TIMEZONE,
            $storedRecord->scheduled_start_at->getTimezone()->getName(),
        );
        $this->assertSame('2026-10-29 04:30:00', $storedRecord->check_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $storedRecord->check_out_at->getTimezone()->getName());
        $this->assertSame('UTC', $storedEvent->event_at->getTimezone()->getName());
        $this->assertSame(
            '2026-10-29 10:00:00',
            AttendanceClock::toLocalDisplay($storedEvent->event_at)->format('Y-m-d H:i:s'),
        );
    }

    public function test_work_date_uses_kolkata_calendar_day_at_utc_day_boundary(): void
    {
        $beforeMidnightKolkata = CarbonImmutable::parse('2026-10-28T18:29:59Z');
        $atMidnightKolkata = CarbonImmutable::parse('2026-10-28T18:30:00Z');

        $this->assertSame('2026-10-28', AttendanceClock::workDate($beforeMidnightKolkata)->toDateString());
        $this->assertSame('2026-10-29', AttendanceClock::workDate($atMidnightKolkata)->toDateString());
        $this->assertSame(
            AttendanceClock::LOCAL_TIMEZONE,
            AttendanceClock::workDate($atMidnightKolkata)->getTimezone()->getName(),
        );
    }

    public function test_schedule_snapshot_uses_assignment_dates_and_kolkata_local_wall_clock_values(): void
    {
        [, $assignment] = $this->createAttendanceSetup();
        $instant = CarbonImmutable::parse('2026-10-28T18:30:00Z');
        $snapshot = AttendanceScheduleSnapshot::fromAssignment($assignment, $instant);

        $this->assertSame('2026-10-29', $snapshot->workDate->toDateString());
        $this->assertSame('2026-10-29 10:00:00', $snapshot->scheduledStartAt->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-29 19:00:00', $snapshot->scheduledEndAt->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-29 10:15:00', $snapshot->lateAfterAt->format('Y-m-d H:i:s'));
        $this->assertSame(AttendanceClock::LOCAL_TIMEZONE, $snapshot->scheduledStartAt->getTimezone()->getName());
    }

    public function test_check_in_is_late_only_strictly_after_the_grace_threshold(): void
    {
        [, $assignment] = $this->createAttendanceSetup();
        $snapshot = AttendanceScheduleSnapshot::fromAssignment(
            $assignment,
            CarbonImmutable::parse('2026-10-28T18:30:00Z'),
        );
        $thresholdUtc = $snapshot->lateAfterAt->setTimezone('UTC');

        $this->assertFalse($snapshot->isLate($thresholdUtc));
        $this->assertTrue($snapshot->isLate($thresholdUtc->addSecond()));
        $this->assertFalse($snapshot->isLate($thresholdUtc->subSecond()));
    }

    public function test_schedule_builder_rejects_an_assignment_outside_its_inclusive_effective_dates(): void
    {
        [, $assignment] = $this->createAttendanceSetup(effectiveFrom: '2026-10-29', effectiveTo: '2026-10-29');

        $accepted = AttendanceScheduleSnapshot::fromAssignment(
            $assignment,
            CarbonImmutable::parse('2026-10-28T18:30:00Z'),
        );
        $this->assertSame('2026-10-29', $accepted->workDate->toDateString());

        $this->expectException(\InvalidArgumentException::class);
        AttendanceScheduleSnapshot::fromAssignment(
            $assignment,
            CarbonImmutable::parse('2026-10-29T18:30:00Z'),
        );
    }

    public function test_time_conversions_do_not_depend_on_the_php_default_timezone(): void
    {
        $originalTimezone = date_default_timezone_get();

        try {
            date_default_timezone_set('Pacific/Honolulu');
            $instant = CarbonImmutable::parse('2026-10-28T18:30:00Z');

            $this->assertSame('2026-10-29', AttendanceClock::workDate($instant)->toDateString());
            $this->assertSame(
                '2026-10-29 00:00:00',
                AttendanceClock::toLocalDisplay($instant)->format('Y-m-d H:i:s'),
            );
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    /** @return array{Employee, EmployeeShiftAssignment} */
    private function createAttendanceSetup(
        string $effectiveFrom = '2026-01-01',
        ?string $effectiveTo = null,
    ): array {
        $department = Department::create(['name' => 'Stitching']);
        $user = User::factory()->create([
            'name' => 'Test Employee',
            'email' => 'employee@example.test',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-001',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);
        $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'role' => User::ROLE_HR_ADMIN,
            'is_active' => true,
        ]);
        $shift = Shift::create([
            'name' => 'Day Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'assigned_by_user_id' => $admin->id,
        ]);

        return [$employee, $assignment];
    }
}
