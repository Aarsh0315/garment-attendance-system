<?php

namespace Tests\Feature;

use App\Casts\KolkataDateTime;
use App\Casts\UtcDateTime;
use App\Models\AttendanceAuditLog;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\OfficeLocation;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AttendanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_tables_have_the_approved_columns(): void
    {
        $schema = $this->app['db']->getSchemaBuilder();

        foreach ([
            'shifts',
            'employee_shift_assignments',
            'office_locations',
            'attendance_records',
            'attendance_events',
            'attendance_audit_logs',
        ] as $table) {
            $this->assertTrue($schema->hasTable($table), "The {$table} table should exist.");
        }

        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'start_time', 'end_time', 'ends_next_day', 'late_grace_minutes', 'is_active', 'created_at', 'updated_at'],
            $schema->getColumnListing('shifts'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'employee_id', 'shift_id', 'effective_from', 'effective_to', 'assigned_by_user_id', 'created_at', 'updated_at'],
            $schema->getColumnListing('employee_shift_assignments'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'environment', 'latitude', 'longitude', 'radius_meters', 'is_active', 'active_environment', 'created_at', 'updated_at'],
            $schema->getColumnListing('office_locations'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'employee_id', 'employee_shift_assignment_id', 'work_date', 'scheduled_start_at', 'scheduled_end_at', 'late_after_at', 'check_in_at', 'check_out_at', 'created_at', 'updated_at'],
            $schema->getColumnListing('attendance_records'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'employee_id', 'attendance_record_id', 'actor_user_id', 'office_location_id', 'event_type', 'outcome', 'source', 'event_at', 'latitude', 'longitude', 'accuracy_meters', 'distance_meters', 'rejection_code', 'created_at'],
            $schema->getColumnListing('attendance_events'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'attendance_record_id', 'actor_user_id', 'action', 'reason', 'before_values', 'after_values', 'created_at'],
            $schema->getColumnListing('attendance_audit_logs'),
        );
        $this->assertFalse($schema->hasColumn('attendance_records', 'recorded_elapsed_seconds'));
        $this->assertFalse($schema->hasColumn('attendance_events', 'updated_at'));
        $this->assertFalse($schema->hasColumn('attendance_audit_logs', 'updated_at'));
        $this->assertSame('datetime', strtolower($schema->getColumnType('attendance_events', 'event_at')));
    }

    public function test_attendance_models_and_user_relationships_work(): void
    {
        [$employee, $admin, $shift, $assignment] = $this->createAttendanceSetup();
        $location = $this->createLocation('development', true);
        $record = $this->createAttendanceRecord($employee, $assignment);
        $event = $this->createAttendanceEvent([
            'employee_id' => $employee->id,
            'attendance_record_id' => $record->id,
            'actor_user_id' => $employee->user_id,
            'office_location_id' => $location->id,
            'event_type' => 'check_in',
            'outcome' => 'accepted',
            'source' => 'employee_web',
            'event_at' => '2026-10-27 10:00:00',
        ]);
        $audit = $this->createAuditLog([
            'attendance_record_id' => $record->id,
            'actor_user_id' => $admin->id,
            'action' => 'correction',
            'reason' => 'Approved correction reason',
            'before_values' => ['check_in_at' => null],
            'after_values' => ['check_in_at' => '2026-10-27 10:00:00'],
        ]);

        $this->assertTrue($employee->shiftAssignments->first()->is($assignment));
        $this->assertTrue($assignment->employee->is($employee));
        $this->assertTrue($assignment->shift->is($shift));
        $this->assertTrue($assignment->assignedBy->is($admin));
        $this->assertTrue($assignment->attendanceRecords->first()->is($record));
        $this->assertTrue($employee->attendanceRecords->first()->is($record));
        $this->assertTrue($record->employeeShiftAssignment->is($assignment));
        $this->assertTrue($record->events->first()->is($event));
        $this->assertTrue($record->auditLogs->first()->is($audit));
        $this->assertTrue($event->employee->is($employee));
        $this->assertTrue($event->attendanceRecord->is($record));
        $this->assertTrue($event->actor->is($employee->user));
        $this->assertTrue($event->officeLocation->is($location));
        $this->assertTrue($audit->attendanceRecord->is($record));
        $this->assertTrue($audit->actor->is($admin));
        $this->assertTrue($admin->assignedShiftAssignments->first()->is($assignment));
        $this->assertTrue($employee->user->attendanceEvents->first()->is($event));
        $this->assertTrue($admin->attendanceAuditLogs->first()->is($audit));
        $this->assertSame('date', $assignment->getCasts()['effective_from']);
        $this->assertSame(KolkataDateTime::class, $record->getCasts()['scheduled_start_at']);
        $this->assertSame(UtcDateTime::class, $record->getCasts()['check_in_at']);
        $this->assertSame(UtcDateTime::class, $event->getCasts()['event_at']);
        $this->assertSame('array', $audit->getCasts()['before_values']);
    }

    public function test_one_attendance_record_per_employee_and_work_date_is_enforced(): void
    {
        [$employee, , , $assignment] = $this->createAttendanceSetup();
        $this->createAttendanceRecord($employee, $assignment);

        $this->expectException(QueryException::class);
        $this->createAttendanceRecord($employee, $assignment);
    }

    public function test_composite_foreign_keys_keep_assignments_and_records_with_the_same_employee(): void
    {
        [$employee, , , $assignment] = $this->createAttendanceSetup();
        $otherDepartment = Department::create(['name' => 'Finishing']);
        $otherEmployeeUser = $this->createUser('other-employee@example.test', User::ROLE_EMPLOYEE);
        $otherEmployee = Employee::create([
            'user_id' => $otherEmployeeUser->id,
            'employee_code' => 'EMP-002',
            'department_id' => $otherDepartment->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);
        $otherAdmin = $this->createUser('other-admin@example.test', User::ROLE_HR_ADMIN);
        $otherShift = Shift::create([
            'name' => 'Evening',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'late_grace_minutes' => 15,
        ]);
        $otherAssignment = EmployeeShiftAssignment::create([
            'employee_id' => $otherEmployee->id,
            'shift_id' => $otherShift->id,
            'effective_from' => '2026-01-01',
            'assigned_by_user_id' => $otherAdmin->id,
        ]);

        try {
            $this->createAttendanceRecord($employee, $otherAssignment);
            $this->fail('An employee must not reference another employee\'s shift assignment.');
        } catch (QueryException) {
            $this->assertDatabaseCount('attendance_records', 0);
        }

        $record = $this->createAttendanceRecord($employee, $assignment);

        $this->expectException(QueryException::class);
        $this->createAttendanceEvent([
            'employee_id' => $otherEmployee->id,
            'attendance_record_id' => $record->id,
            'actor_user_id' => $otherEmployeeUser->id,
            'event_type' => 'check_in',
            'outcome' => 'rejected',
            'source' => 'employee_web',
            'event_at' => '2026-10-27 10:01:00',
        ]);
    }

    public function test_restrictive_foreign_keys_preserve_attendance_history(): void
    {
        [$employee, , $shift, $assignment] = $this->createAttendanceSetup();
        $record = $this->createAttendanceRecord($employee, $assignment);

        try {
            $assignment->delete();
            $this->fail('A shift assignment referenced by attendance must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('employee_shift_assignments', ['id' => $assignment->id]);
        }

        try {
            $shift->delete();
            $this->fail('A shift referenced by assignment history must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
        }

        try {
            $employee->delete();
            $this->fail('An employee referenced by attendance must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('attendance_records', ['id' => $record->id]);
        }
    }

    public function test_shift_assignments_support_inclusive_end_date_ranges(): void
    {
        [$employee, $admin, $shift] = $this->createAttendanceSetup();
        $assignment = $employee->shiftAssignments()->firstOrFail();
        $assignment->effective_to = '2026-01-31';
        $assignment->save();
        $ongoingAssignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-02-01',
            'assigned_by_user_id' => $admin->id,
        ]);

        $this->assertSame('2026-01-01', $assignment->fresh()->effective_from->toDateString());
        $this->assertSame('2026-01-31', $assignment->fresh()->effective_to->toDateString());
        $this->assertNull($ongoingAssignment->fresh()->effective_to);
    }

    public function test_schedule_snapshots_and_punch_timestamps_are_stored_without_derived_elapsed_seconds(): void
    {
        [$employee, , , $assignment] = $this->createAttendanceSetup();
        $record = $this->createAttendanceRecord($employee, $assignment);

        $this->assertSame('2026-10-27 10:00:00', $record->fresh()->scheduled_start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-27 19:00:00', $record->fresh()->scheduled_end_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-27 10:15:00', $record->fresh()->late_after_at->format('Y-m-d H:i:s'));
        $this->assertFalse($this->app['db']->getSchemaBuilder()->hasColumn('attendance_records', 'recorded_elapsed_seconds'));
    }

    public function test_event_schema_allows_rejected_attempts_without_a_daily_record_or_event_type_uniqueness(): void
    {
        [$employee] = $this->createAttendanceSetup();
        $user = $employee->user;

        foreach (['2026-10-27 09:00:00', '2026-10-27 09:01:00'] as $eventAt) {
            $this->createAttendanceEvent([
                'employee_id' => $employee->id,
                'actor_user_id' => $user->id,
                'event_type' => 'check_in',
                'outcome' => 'rejected',
                'source' => 'employee_web',
                'event_at' => $eventAt,
                'rejection_code' => 'outside_boundary',
            ]);
        }

        $this->assertDatabaseCount('attendance_events', 2);
        $this->assertDatabaseHas('attendance_events', ['attendance_record_id' => null]);
    }

    public function test_only_one_active_office_location_per_environment_is_allowed(): void
    {
        $development = $this->createLocation('development', true);
        $production = $this->createLocation('production', true);
        $inactiveDevelopment = $this->createLocation('development', false);
        $secondInactiveDevelopment = $this->createLocation('development', false);

        $this->assertSame('development', $development->environment);
        $this->assertSame('production', $production->environment);
        $this->assertFalse($inactiveDevelopment->is_active);
        $this->assertFalse($secondInactiveDevelopment->is_active);
        $this->assertDatabaseHas('office_locations', ['id' => $inactiveDevelopment->id, 'is_active' => false]);
        $this->assertDatabaseHas('office_locations', ['id' => $secondInactiveDevelopment->id, 'is_active' => false]);

        $this->expectException(QueryException::class);
        $this->createLocation('development', true);
    }

    public function test_attendance_timezone_is_asia_kolkata(): void
    {
        $this->assertSame('Asia/Kolkata', config('app.timezone'));
    }

    public function test_audit_entries_are_append_only_through_eloquent(): void
    {
        [$employee, $admin, , $assignment] = $this->createAttendanceSetup();
        $record = $this->createAttendanceRecord($employee, $assignment);
        $audit = $this->createAuditLog([
            'attendance_record_id' => $record->id,
            'actor_user_id' => $admin->id,
            'action' => 'correction',
            'reason' => 'Required test reason',
        ]);

        try {
            $audit->reason = 'Changed reason';
            $audit->save();
            $this->fail('Audit entries must not be updated.');
        } catch (LogicException) {
            $this->assertSame('Required test reason', $audit->fresh()->reason);
        }

        $this->expectException(LogicException::class);
        $audit->delete();
    }

    public function test_attendance_event_and_audit_log_fields_are_not_mass_assignable(): void
    {
        $event = new AttendanceEvent;
        try {
            $event->fill([
                'employee_id' => 1,
                'actor_user_id' => 2,
                'event_type' => 'check_in',
                'outcome' => 'accepted',
                'event_at' => '2026-10-27 10:00:00',
            ]);
            $this->fail('Untrusted event attributes must be rejected.');
        } catch (MassAssignmentException) {
            $this->assertSame([], $event->getAttributes());
        }
        $audit = new AttendanceAuditLog;
        try {
            $audit->fill([
                'attendance_record_id' => 1,
                'actor_user_id' => 2,
                'action' => 'correction',
                'reason' => 'Untrusted input',
                'after_values' => ['check_in_at' => '2026-10-27 10:00:00'],
            ]);
            $this->fail('Untrusted audit attributes must be rejected.');
        } catch (MassAssignmentException) {
            $this->assertSame([], $audit->getAttributes());
        }
        $this->assertFalse($event->isFillable('actor_user_id'));
        $this->assertFalse($audit->isFillable('actor_user_id'));
    }

    /** @return array{Employee, User, Shift, EmployeeShiftAssignment} */
    private function createAttendanceSetup(): array
    {
        $department = Department::create(['name' => 'Stitching']);
        $employeeUser = $this->createUser('employee@example.test', User::ROLE_EMPLOYEE);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-001',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);
        $admin = $this->createUser('admin@example.test', User::ROLE_HR_ADMIN);
        $shift = Shift::create([
            'name' => 'Day Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'late_grace_minutes' => 15,
        ]);
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
            'assigned_by_user_id' => $admin->id,
        ]);

        return [$employee, $admin, $shift, $assignment];
    }

    private function createAttendanceRecord(Employee $employee, EmployeeShiftAssignment $assignment): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id' => $employee->id,
            'employee_shift_assignment_id' => $assignment->id,
            'work_date' => '2026-10-27',
            'scheduled_start_at' => '2026-10-27 10:00:00',
            'scheduled_end_at' => '2026-10-27 19:00:00',
            'late_after_at' => '2026-10-27 10:15:00',
        ]);
    }

    /** Trusted test-fixture path mirrors server code: set each server-derived value explicitly, then save. */
    private function createAttendanceEvent(array $attributes): AttendanceEvent
    {
        $event = new AttendanceEvent;
        $event->employee_id = $attributes['employee_id'];
        $event->attendance_record_id = $attributes['attendance_record_id'] ?? null;
        $event->actor_user_id = $attributes['actor_user_id'];
        $event->office_location_id = $attributes['office_location_id'] ?? null;
        $event->event_type = $attributes['event_type'];
        $event->outcome = $attributes['outcome'];
        $event->source = $attributes['source'];
        $event->event_at = $attributes['event_at'];
        $event->latitude = $attributes['latitude'] ?? null;
        $event->longitude = $attributes['longitude'] ?? null;
        $event->accuracy_meters = $attributes['accuracy_meters'] ?? null;
        $event->distance_meters = $attributes['distance_meters'] ?? null;
        $event->rejection_code = $attributes['rejection_code'] ?? null;
        $event->save();

        return $event;
    }

    /** Trusted test-fixture path sets audit values explicitly; never pass request data here. */
    private function createAuditLog(array $attributes): AttendanceAuditLog
    {
        $audit = new AttendanceAuditLog;
        $audit->attendance_record_id = $attributes['attendance_record_id'];
        $audit->actor_user_id = $attributes['actor_user_id'];
        $audit->action = $attributes['action'];
        $audit->reason = $attributes['reason'];
        $audit->before_values = $attributes['before_values'] ?? null;
        $audit->after_values = $attributes['after_values'] ?? null;
        $audit->save();

        return $audit;
    }

    private function createLocation(string $environment, bool $active): OfficeLocation
    {
        static $locationNumber = 0;
        $locationNumber++;

        return OfficeLocation::create([
            'name' => "Test Location {$locationNumber}",
            'environment' => $environment,
            // Numeric placeholders satisfy the schema in SQLite tests; they are not factory coordinates.
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'radius_meters' => 100,
            'is_active' => $active,
        ]);
    }

    private function createUser(string $email, string $role): User
    {
        return User::factory()->create([
            'name' => ucfirst($role),
            'email' => $email,
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
