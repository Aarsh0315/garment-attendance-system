<?php

namespace Tests\Feature;

use App\Models\AttendanceAuditLog;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeShiftAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAttendanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        CarbonImmutable::setTestNow('2026-10-27T19:00:00Z');

        $this->admin = User::factory()->create([
            'role' => User::ROLE_HR_ADMIN,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->department = Department::create(['name' => 'Stitching']);
        $this->shift = Shift::create([
            'name' => 'Day Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'ends_next_day' => false,
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_monitoring_routes_require_hr_admin_and_reject_guests_employees_and_supervisors(): void
    {
        $record = $this->createRecord();
        $this->get(route('hr-admin.attendance.index'))->assertRedirect(route('login'));
        $this->get(route('hr-admin.attendance.show', $record))->assertRedirect(route('login'));

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
                'must_change_password' => false,
            ]);

            $this->actingAs($user)->get(route('hr-admin.attendance.index'))->assertForbidden();
            $this->actingAs($user)->get(route('hr-admin.attendance.show', $record))->assertForbidden();
        }

        $this->actingAs($this->admin)->get(route('hr-admin.attendance.index'))->assertOk();
        $this->get(route('hr-admin.attendance.show', $record))->assertOk();
    }

    public function test_list_defaults_to_kolkata_work_date_and_filters_by_date_state_and_employee_or_department_search(): void
    {
        $complete = $this->createRecord('EMP-001', 'Riya Sharma', 'riya@example.test', '2026-10-28', true);
        $incomplete = $this->createRecord('EMP-002', 'Aman Patel', 'aman@example.test', '2026-10-28', false);
        $this->createRecord('EMP-003', 'Kiran Shah', 'kiran@example.test', '2026-10-27', true);

        $this->actingAs($this->admin)
            ->get(route('hr-admin.attendance.index'))
            ->assertOk()
            ->assertSee('2026-10-28')
            ->assertSee('EMP-001')
            ->assertSee('EMP-002')
            ->assertDontSee('EMP-003')
            ->assertSee('Complete punches')
            ->assertSee('Incomplete punches');

        $this->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-28', 'state' => 'incomplete']))
            ->assertOk()
            ->assertSee('EMP-002')
            ->assertDontSee('EMP-001');

        $this->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-28', 'state' => 'complete']))
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertDontSee('EMP-002');

        foreach (['EMP-001', 'Riya Sharma', 'riya@example.test'] as $search) {
            $this->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-28', 'search' => $search]))
                ->assertOk()
                ->assertSee('EMP-001')
                ->assertDontSee('EMP-002');
        }

        $this->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-28', 'search' => 'Stitching']))
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertSee('EMP-002');

        $this->assertNotNull($complete->id);
        $this->assertNotNull($incomplete->id);
    }

    public function test_attendance_list_paginates_records(): void
    {
        for ($index = 1; $index <= 16; $index++) {
            $this->createRecord(sprintf('EMP-%03d', $index), 'Employee '.$index, 'employee'.$index.'@example.test');
        }

        $this->actingAs($this->admin)
            ->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-28']))
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertDontSee('EMP-016')
            ->assertSee('page=2');

        $this->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-28', 'page' => 2]))
            ->assertOk()
            ->assertSee('EMP-016');
    }

    public function test_empty_state_does_not_infer_absence(): void
    {
        $this->actingAs($this->admin)
            ->get(route('hr-admin.attendance.index', ['work_date' => '2026-10-26']))
            ->assertOk()
            ->assertSee('No matching attendance records')
            ->assertSee('recorded entries only')
            ->assertDontSee('Absent');
    }

    public function test_detail_shows_persisted_local_punches_events_and_audit_history_without_gps_coordinates(): void
    {
        $record = $this->createRecord('EMP-010', 'Test Viewer', 'viewer@example.test');
        $event = new AttendanceEvent;
        $event->employee_id = $record->employee_id;
        $event->attendance_record_id = $record->id;
        $event->actor_user_id = $record->employee->user_id;
        $event->event_type = 'check_in';
        $event->outcome = 'accepted';
        $event->source = 'employee_web';
        $event->event_at = '2026-10-28 04:31:00';
        $event->latitude = '19.1234567';
        $event->longitude = '72.1234567';
        $event->accuracy_meters = '10.00';
        $event->save();

        $audit = new AttendanceAuditLog;
        $audit->attendance_record_id = $record->id;
        $audit->actor_user_id = $this->admin->id;
        $audit->action = 'attendance_reviewed';
        $audit->reason = 'Reviewed against source punch history.';
        $audit->before_values = ['check_in_at' => null];
        $audit->after_values = ['check_in_at' => '2026-10-28 04:31:00'];
        $audit->created_at = '2026-10-28 14:00:00';
        $audit->save();

        $this->actingAs($this->admin)
            ->get(route('hr-admin.attendance.show', $record))
            ->assertOk()
            ->assertSee('10:01:00 AM')
            ->assertSee('7:00:00 PM')
            ->assertSee('Punch event history')
            ->assertSee('Check in')
            ->assertSee('Administrative audit history')
            ->assertSee('Reviewed against source punch history.')
            ->assertDontSee('19.1234567')
            ->assertDontSee('72.1234567');
    }

    private function createRecord(
        string $code = 'EMP-001',
        string $name = 'Riya Sharma',
        string $email = 'riya@example.test',
        string $date = '2026-10-28',
        bool $complete = true,
    ): AttendanceRecord {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'department_id' => $this->department->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);
        $assignment = app(EmployeeShiftAssignmentService::class)->assign(
            $employee,
            $this->shift,
            '2026-01-01',
            null,
            $this->admin,
        );

        $record = new AttendanceRecord;
        $record->employee_id = $employee->id;
        $record->employee_shift_assignment_id = $assignment->id;
        $record->work_date = $date;
        $record->scheduled_start_at = $date.' 10:00:00';
        $record->scheduled_end_at = $date.' 19:00:00';
        $record->late_after_at = $date.' 10:15:00';
        $record->check_in_at = $date.' 04:31:00';
        $record->check_out_at = $complete ? $date.' 13:30:00' : null;
        $record->save();

        return $record->load(['employee.user', 'employee.department']);
    }
}
