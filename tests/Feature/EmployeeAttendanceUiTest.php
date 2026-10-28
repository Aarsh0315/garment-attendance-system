<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeShiftAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EmployeeAttendanceUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Config::set('attendance.location_environment', 'development');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_employee_can_open_personal_attendance_page_with_csrf_protected_punch_forms(): void
    {
        [$employee, $user] = $this->createAttendanceSetup();
        $this->actingAs($user)->get(route('employee.attendance.index'))
            ->assertOk()
            ->assertSee("Today's attendance", false)
            ->assertSee('name="_token"', false)
            ->assertSee('data-attendance-punch', false)
            ->assertSee('data-retry-label="Retry check-in"', false)
            ->assertSee(route('employee.attendance.check-in'), false);
    }

    public function test_hr_and_supervisor_cannot_use_employee_punch_routes(): void
    {
        $this->get(route('employee.attendance.index'))->assertRedirect(route('login'));

        foreach ([User::ROLE_HR_ADMIN, User::ROLE_SUPERVISOR] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
                'must_change_password' => false,
            ]);

            $this->actingAs($user)->get(route('employee.attendance.index'))->assertForbidden();
            $this->actingAs($user)->post(route('employee.attendance.check-in'), $this->gps())->assertForbidden();
            $this->actingAs($user)->post(route('employee.attendance.check-out'), $this->gps())->assertForbidden();
        }
    }

    public function test_employee_can_check_in_and_check_out_with_server_validated_location(): void
    {
        [$employee, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-in'), $this->gps())->assertRedirect(route('employee.attendance.index'));
        $record = AttendanceRecord::query()->firstOrFail();
        $this->assertSame($employee->id, $record->employee_id);
        $this->assertNotNull($record->check_in_at);
        $this->assertDatabaseHas('attendance_events', [
            'attendance_record_id' => $record->id,
            'event_type' => 'check_in',
            'outcome' => 'accepted',
        ]);

        $this->freezeAt('2026-10-28T13:30:00Z');
        $this->post(route('employee.attendance.check-out'), $this->gps())->assertRedirect(route('employee.attendance.index'));
        $this->assertNotNull($record->fresh()->check_out_at);
        $this->assertDatabaseHas('attendance_events', [
            'attendance_record_id' => $record->id,
            'event_type' => 'check_out',
            'outcome' => 'accepted',
        ]);
    }

    public function test_duplicate_check_in_and_check_out_without_check_in_are_rejected(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-out'), $this->gps())->assertSessionHasErrors('attendance');
        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);

        $this->post(route('employee.attendance.check-in'), $this->gps())->assertRedirect();
        $this->post(route('employee.attendance.check-in'), $this->gps())->assertSessionHasErrors('attendance');
        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseCount('attendance_events', 1);
    }

    public function test_missing_and_invalid_gps_values_create_no_attendance_or_accepted_events(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-in'), [])->assertSessionHasErrors([
            'latitude', 'longitude', 'accuracy_meters',
        ]);
        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), ['latitude' => 1]))
            ->assertSessionHasErrors('location');
        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), ['accuracy_meters' => 101]))
            ->assertSessionHasErrors('accuracy_meters');

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_accuracy_at_the_server_limit_is_accepted(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), [
            'accuracy_meters' => '100',
        ]))->assertRedirect();

        $this->assertDatabaseHas('attendance_events', [
            'event_type' => 'check_in',
            'accuracy_meters' => '100.00',
        ]);
    }

    public function test_local_diagnostics_identify_server_accuracy_rejection_without_coordinates(): void
    {
        config(['app.env' => 'local']);
        [, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), [
            'accuracy_meters' => '123.456',
        ]))->assertSessionHas('attendance_diagnostics', function (array $diagnostics): bool {
            $this->assertSame('server_rejected_accuracy', $diagnostics['category']);
            $this->assertSame(123.5, $diagnostics['reported_accuracy_meters']);
            $this->assertTrue($diagnostics['latitude_valid']);
            $this->assertTrue($diagnostics['longitude_valid']);
            $this->assertFalse($diagnostics['accuracy_valid']);
            $this->assertSame(['accuracy_meters'], $diagnostics['server_validation_fields']);
            $this->assertArrayNotHasKey('latitude', $diagnostics);
            $this->assertArrayNotHasKey('longitude', $diagnostics);

            return true;
        });

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_local_diagnostics_distinguish_boundary_rejection_from_accuracy(): void
    {
        config(['app.env' => 'local']);
        [, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), [
            'latitude' => '1',
        ]))->assertSessionHas('attendance_diagnostics', function (array $diagnostics): bool {
            $this->assertSame('server_rejected_location_boundary', $diagnostics['category']);
            $this->assertSame(10.0, $diagnostics['reported_accuracy_meters']);
            $this->assertTrue($diagnostics['accuracy_valid']);
            $this->assertSame(['location'], $diagnostics['server_validation_fields']);

            return true;
        });

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    public function test_diagnostics_are_not_rendered_or_flashed_outside_local_environment(): void
    {
        config(['app.env' => 'production']);
        [, $user] = $this->createAttendanceSetup();
        $this->actingAs($user);

        $this->get(route('employee.attendance.index'))
            ->assertOk()
            ->assertDontSee('Temporary development diagnostics');

        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), [
            'accuracy_meters' => '101',
        ]))->assertSessionMissing('attendance_diagnostics');
    }

    public function test_geolocation_configuration_and_accuracy_units_remain_explicit_in_client_code(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('enableHighAccuracy: true', $script);
        $this->assertStringContainsString('timeout: 15000', $script);
        $this->assertStringContainsString('maximumAge: 0', $script);
        $this->assertStringContainsString('accuracy <= 100', $script);
        $this->assertStringContainsString('accuracy.toFixed(1)', $script);
        $this->assertStringContainsString('[name="accuracy_meters"]', $script);
        $this->assertStringContainsString('String(accuracy)', $script);
        $this->assertStringContainsString('browser_accuracy_exceeds_100_metres', $script);
        $this->assertStringContainsString('browser_accuracy_invalid', $script);
        $this->assertStringContainsString('browser_error_code: error.code', $script);
        $this->assertStringContainsString('server_rejection: \'Pending\'', $script);
    }

    public function test_request_cannot_target_another_employees_attendance(): void
    {
        [$employee, $user] = $this->createAttendanceSetup();
        [$otherEmployee] = $this->createAttendanceSetup('EMP-002', 'other@example.test');
        $this->actingAs($user);
        $this->freezeAt('2026-10-28T04:30:00Z');

        $this->post(route('employee.attendance.check-in'), array_merge($this->gps(), [
            'employee_id' => $otherEmployee->id,
        ]))->assertRedirect();

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertSame($employee->id, AttendanceRecord::query()->firstOrFail()->employee_id);
        $this->assertSame(0, AttendanceRecord::query()->where('employee_id', $otherEmployee->id)->count());
    }

    public function test_password_change_requirement_still_blocks_attendance_page(): void
    {
        [, $user] = $this->createAttendanceSetup();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)->get(route('employee.attendance.index'))
            ->assertRedirect(route('password.change.edit'));
    }

    private function createAttendanceSetup(string $employeeCode = 'EMP-001', string $email = 'employee@example.test'): array
    {
        $user = User::factory()->create([
            'name' => 'Test Employee '.$employeeCode,
            'email' => $email,
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $department = Department::create(['name' => 'Stitching '.$employeeCode]);
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
            'must_change_password' => false,
        ]);
        $shift = Shift::create([
            'name' => 'Day shift '.$employeeCode,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'ends_next_day' => false,
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);
        app(EmployeeShiftAssignmentService::class)->assign($employee, $shift, '2026-01-01', null, $admin);

        if (! OfficeLocation::query()->where('environment', 'development')->where('is_active', true)->exists()) {
            OfficeLocation::create([
                'name' => 'Test Location',
                'environment' => 'development',
                'latitude' => '0.0000000',
                'longitude' => '0.0000000',
                'radius_meters' => 100,
                'is_active' => true,
            ]);
        }

        return [$employee, $user];
    }

    /** @return array{latitude: string, longitude: string, accuracy_meters: string} */
    private function gps(): array
    {
        return ['latitude' => '0', 'longitude' => '0', 'accuracy_meters' => '10'];
    }

    private function freezeAt(string $instant): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($instant));
    }
}
