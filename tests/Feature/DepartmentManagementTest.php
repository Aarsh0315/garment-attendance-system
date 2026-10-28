<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\EmployeeSupervisorAssignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_and_non_hr_users_cannot_manage_departments(): void
    {
        $department = Department::create(['name' => 'Cutting']);

        $this->get(route('hr-admin.departments.index'))->assertRedirect(route('login'));
        $this->get(route('hr-admin.departments.create'))->assertRedirect(route('login'));
        $this->get(route('hr-admin.departments.show', $department))->assertRedirect(route('login'));
        $this->get(route('hr-admin.departments.edit', $department))->assertRedirect(route('login'));
        $this->post(route('hr-admin.departments.store'), ['name' => 'Cutting'])
            ->assertRedirect(route('login'));
        $this->patch(route('hr-admin.departments.activate', $department))
            ->assertRedirect(route('login'));

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR] as $role) {
            $user = $this->createUser($role);

            $this->actingAs($user)
                ->get(route('hr-admin.departments.index'))
                ->assertForbidden();
            $this->actingAs($user)
                ->get(route('hr-admin.departments.create'))
                ->assertForbidden();
            $this->actingAs($user)
                ->get(route('hr-admin.departments.show', $department))
                ->assertForbidden();
            $this->actingAs($user)
                ->get(route('hr-admin.departments.edit', $department))
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('hr-admin.departments.store'), ['name' => 'Finishing'])
                ->assertForbidden();
            $this->actingAs($user)
                ->put(route('hr-admin.departments.update', $department), ['name' => 'Finishing'])
                ->assertForbidden();
            $this->actingAs($user)
                ->patch(route('hr-admin.departments.deactivate', $department))
                ->assertForbidden();
            $this->actingAs($user)
                ->patch(route('hr-admin.departments.activate', $department))
                ->assertForbidden();
        }

        $this->assertDatabaseCount('departments', 1);
    }

    public function test_hr_admin_can_create_view_and_edit_a_department(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN));

        $this->get(route('hr-admin.departments.index'))
            ->assertOk()
            ->assertSee('Departments');
        $this->get(route('hr-admin.departments.create'))
            ->assertOk()
            ->assertSee('Department name');

        $this->post(route('hr-admin.departments.store'), [
            'name' => '  Quality Control  ',
            'is_active' => false,
        ])
            ->assertRedirect();

        $department = Department::query()->firstOrFail();
        $this->assertSame('Quality Control', $department->name);
        $this->assertSame('quality control', $department->normalized_name);
        $this->assertTrue($department->fresh()->is_active);
        $this->assertSame('Department created successfully.', session('status'));

        $this->get(route('hr-admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Quality Control');
        $this->get(route('hr-admin.departments.edit', $department))
            ->assertOk()
            ->assertSee('Quality Control');

        $this->put(route('hr-admin.departments.update', $department), [
            'name' => '  quality control  ',
            'is_active' => false,
        ])->assertRedirect(route('hr-admin.departments.show', $department));

        $this->assertSame('quality control', $department->fresh()->name);
        $this->assertSame('quality control', $department->fresh()->normalized_name);
        $this->assertTrue($department->fresh()->is_active, 'Status is changed only through the deactivation action.');
        $this->assertSame('Department updated successfully.', session('status'));
    }

    public function test_department_name_is_required_and_unique_without_regard_to_case(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN));
        $department = Department::create(['name' => 'Assembly']);

        $this->from(route('hr-admin.departments.create'))
            ->post(route('hr-admin.departments.store'), ['name' => '   '])
            ->assertSessionHasErrors('name');

        $this->from(route('hr-admin.departments.create'))
            ->post(route('hr-admin.departments.store'), ['name' => '  aSSeMbLy  '])
            ->assertSessionHasErrors('name');

        $this->from(route('hr-admin.departments.edit', $department))
            ->put(route('hr-admin.departments.update', $department), ['name' => ' ASSEMBLY '])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('departments', 1);
        $this->assertSame('ASSEMBLY', $department->fresh()->name);
    }

    public function test_department_list_searches_and_shows_status_appropriate_actions(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN));
        $active = Department::create(['name' => 'Cutting']);
        $inactive = Department::create(['name' => 'Finishing', 'is_active' => false]);

        $this->get(route('hr-admin.departments.index', ['search' => 'Cut']))
            ->assertOk()
            ->assertSee('Cutting')
            ->assertSee(route('hr-admin.departments.show', $active))
            ->assertDontSee('Deactivate')
            ->assertDontSee('Activate')
            ->assertDontSee('Finishing');

        $this->get(route('hr-admin.departments.index', ['search' => 'Finish']))
            ->assertOk()
            ->assertSee('Finishing')
            ->assertSee(route('hr-admin.departments.show', $inactive))
            ->assertDontSee('Deactivate')
            ->assertDontSee('Activate')
            ->assertDontSee('Cutting');

        $this->get(route('hr-admin.departments.show', $active))
            ->assertOk()
            ->assertSee('Deactivate department')
            ->assertSee('data-confirm=', false);
        $this->get(route('hr-admin.departments.show', $inactive))
            ->assertOk()
            ->assertSee('Activate department')
            ->assertSee('data-confirm=', false);

        $this->get(route('hr-admin.departments.index', ['search' => 'missing']))
            ->assertOk()
            ->assertSee('No departments match your search.');
        $this->get(route('hr-admin.departments.index', ['search' => str_repeat('x', 101)]))
            ->assertSessionHasErrors('search');
    }

    public function test_reactivation_preserves_department_employees_attendance_and_assignment_history(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $department = Department::create(['name' => 'Packing', 'is_active' => false]);
        $employeeUser = $this->createUser(User::ROLE_EMPLOYEE, 'employee@example.test');
        $employeeUser->is_active = false;
        $employeeUser->must_change_password = true;
        $employeeUser->save();
        $originalPassword = $employeeUser->password;
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-501',
            'department_id' => $department->id,
            'designation' => 'Packer',
            'joining_date' => '2025-06-01',
        ]);
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $supervisorAssignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-06-01',
        ]);
        $shift = Shift::create([
            'name' => 'Day Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'late_grace_minutes' => 15,
        ]);
        $shiftAssignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2025-06-01',
            'assigned_by_user_id' => $admin->id,
        ]);
        $attendance = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'employee_shift_assignment_id' => $shiftAssignment->id,
            'work_date' => '2025-09-15',
            'scheduled_start_at' => '2025-09-15 10:00:00',
            'scheduled_end_at' => '2025-09-15 19:00:00',
            'late_after_at' => '2025-09-15 10:15:00',
            'check_in_at' => '2025-09-15 04:30:00',
        ]);
        $event = new AttendanceEvent;
        $event->employee_id = $employee->id;
        $event->attendance_record_id = $attendance->id;
        $event->actor_user_id = $employeeUser->id;
        $event->event_type = 'check_in';
        $event->outcome = 'accepted';
        $event->source = 'employee_web';
        $event->event_at = '2025-09-15 04:30:00';
        $event->save();

        $this->get(route('hr-admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Activate department')
            ->assertSee('data-confirm=', false);
        $this->get(route('hr-admin.employees.create'))
            ->assertOk()
            ->assertDontSee('value="'.$department->id.'"', false);
        $this->patch(route('hr-admin.departments.activate', $department))
            ->assertRedirect(route('hr-admin.departments.show', $department));

        $this->assertTrue($department->fresh()->is_active);
        $this->assertSame('Packing', $department->fresh()->name);
        $this->assertSame('packing', $department->fresh()->normalized_name);
        $this->assertSame($department->id, $employee->fresh()->department_id);
        $this->assertFalse($employeeUser->fresh()->is_active);
        $this->assertTrue($employeeUser->fresh()->must_change_password);
        $this->assertSame(User::ROLE_EMPLOYEE, $employeeUser->fresh()->role);
        $this->assertSame($originalPassword, $employeeUser->fresh()->password);
        $this->assertSame('2025-06-01', $supervisorAssignment->fresh()->effective_from->toDateString());
        $this->assertNull($supervisorAssignment->fresh()->effective_to);
        $this->assertDatabaseHas('employee_shift_assignments', ['id' => $shiftAssignment->id]);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'employee_id' => $employee->id,
            'check_in_at' => '2025-09-15 04:30:00',
        ]);
        $this->assertDatabaseHas('attendance_events', [
            'id' => $event->id,
            'employee_id' => $employee->id,
            'event_type' => 'check_in',
            'event_at' => '2025-09-15 04:30:00',
        ]);
        $this->get(route('hr-admin.employees.create'))
            ->assertOk()
            ->assertSee('value="'.$department->id.'"', false);

        $this->post(route('hr-admin.employees.store'), [
            'name' => 'New Packer',
            'email' => 'new.packer@example.test',
            'employee_code' => 'EMP-502',
            'department_id' => $department->id,
            'designation' => 'Packer',
            'joining_date' => '2026-01-01',
            'password' => 'Temporary-Password-123',
            'password_confirmation' => 'Temporary-Password-123',
        ])->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-502',
            'department_id' => $department->id,
        ]);
    }

    public function test_activation_of_an_already_active_department_is_safe_and_does_not_change_employees(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN));
        $department = Department::create(['name' => 'Assembly']);
        $employeeUser = $this->createUser(User::ROLE_EMPLOYEE, 'active-employee@example.test');
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-503',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-01',
        ]);

        $this->patch(route('hr-admin.departments.activate', $department))
            ->assertRedirect(route('hr-admin.departments.show', $department));

        $this->assertSame('This department is already active.', session('error'));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'is_active' => true]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'department_id' => $department->id]);
        $this->assertTrue($employeeUser->fresh()->is_active);
        $this->assertSame(User::ROLE_EMPLOYEE, $employeeUser->fresh()->role);
    }

    public function test_deactivation_retains_the_department_and_employee_link_and_keeps_it_visible(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN));
        $department = Department::create(['name' => 'Packing']);
        $employeeUser = $this->createUser(User::ROLE_EMPLOYEE, 'employee@example.test');
        Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-500',
            'department_id' => $department->id,
            'designation' => 'Packer',
            'joining_date' => '2025-06-01',
        ]);

        $this->patch(route('hr-admin.departments.deactivate', $department))
            ->assertRedirect(route('hr-admin.departments.show', $department));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('employees', [
            'department_id' => $department->id,
            'employee_code' => 'EMP-500',
        ]);
        $this->assertTrue($department->employees()->exists());

        $this->get(route('hr-admin.departments.index'))
            ->assertOk()
            ->assertSee('Packing')
            ->assertSee('Inactive');
        $this->get(route('hr-admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Packing')
            ->assertSee('Inactive')
            ->assertSee('1');
    }

    public function test_an_inactive_department_is_not_hard_deleted_or_deactivated_twice(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN));
        $department = Department::create(['name' => 'Stitching', 'is_active' => false]);

        $this->delete(route('hr-admin.departments.show', $department))
            ->assertMethodNotAllowed();

        $this->patch(route('hr-admin.departments.deactivate', $department))
            ->assertRedirect(route('hr-admin.departments.show', $department));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => false,
        ]);
        $this->assertSame('This department is already inactive.', session('error'));
    }

    private function createUser(string $role, string $email = ''): User
    {
        $user = new User;
        $user->name = 'Department Test User';
        $user->email = $email !== '' ? $email : $role.'@example.test';
        $user->password = Hash::make('test-password');
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
