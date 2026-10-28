<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeShiftAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_shift_assignment_management_is_hr_admin_only(): void
    {
        $index = route('hr-admin.shift-assignments.index');
        $create = route('hr-admin.shift-assignments.create');
        $store = route('hr-admin.shift-assignments.store');
        $this->get($index)->assertRedirect(route('login'));
        $this->get($create)->assertRedirect(route('login'));
        $this->post($store, [])->assertRedirect(route('login'));

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR] as $role) {
            $this->actingAs($this->createUser($role, strtolower($role).'@example.test'));
            $this->get($index)->assertForbidden();
            $this->get($create)->assertForbidden();
            $this->post($store, [])->assertForbidden();
        }
    }

    public function test_hr_admin_can_assign_shift_and_view_inclusive_history(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $employee = $this->createEmployee();
        $shift = $this->createShift();

        $this->get(route('hr-admin.shift-assignments.index'))
            ->assertOk()
            ->assertSee('No shift assignments have been recorded.');
        $this->get(route('hr-admin.shift-assignments.create'))
            ->assertOk()
            ->assertSee('Select an active employee')
            ->assertSee('Select an active shift');

        $this->post(route('hr-admin.shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-03-01',
            'effective_to' => '2026-03-31',
        ])->assertRedirect(route('hr-admin.shift-assignments.index'));

        $assignment = EmployeeShiftAssignment::query()->firstOrFail();
        $this->assertSame($admin->id, $assignment->assigned_by_user_id);
        $this->get(route('hr-admin.shift-assignments.index'))
            ->assertOk()
            ->assertSee($employee->employee_code)
            ->assertSee($shift->name)
            ->assertSee('2026-03-01')
            ->assertSee('2026-03-31');
        $this->get(route('hr-admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Shift assignment history')
            ->assertSee($shift->name);
    }

    public function test_overlapping_ranges_and_ineligible_employee_or_shift_are_rejected_without_writes(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $employee = $this->createEmployee();
        $shift = $this->createShift();
        $existing = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-01-31',
            'assigned_by_user_id' => $admin->id,
        ]);

        $this->from(route('hr-admin.shift-assignments.create'))
            ->post(route('hr-admin.shift-assignments.store'), [
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'effective_from' => '2026-01-31',
                'effective_to' => '2026-02-10',
            ])
            ->assertSessionHasErrors('effective_from');

        $inactiveEmployee = $this->createEmployee('inactive@example.test', 'EMP-101', false);
        $inactiveShift = $this->createShift('Inactive shift', false);
        foreach ([
            ['employee_id' => $inactiveEmployee->id, 'shift_id' => $shift->id, 'field' => 'employee_id'],
            ['employee_id' => $employee->id, 'shift_id' => $inactiveShift->id, 'field' => 'shift_id'],
        ] as $case) {
            $this->post(route('hr-admin.shift-assignments.store'), [
                'employee_id' => $case['employee_id'],
                'shift_id' => $case['shift_id'],
                'effective_from' => '2026-02-01',
                'effective_to' => null,
            ])->assertSessionHasErrors($case['field']);
        }

        $this->assertDatabaseCount('employee_shift_assignments', 1);
        $this->assertSame('2026-01-31', $existing->fresh()->effective_to->toDateString());
    }

    public function test_invalid_date_range_is_rejected_and_gaps_remain_permitted(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $employee = $this->createEmployee();
        $shift = $this->createShift();

        $this->from(route('hr-admin.shift-assignments.create'))
            ->post(route('hr-admin.shift-assignments.store'), [
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'effective_from' => '2026-02-10',
                'effective_to' => '2026-02-09',
            ])
            ->assertSessionHasErrors('effective_to');

        $this->post(route('hr-admin.shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-01-10',
        ])->assertRedirect();
        $this->post(route('hr-admin.shift-assignments.store'), [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-01-20',
            'effective_to' => null,
        ])->assertRedirect();

        $this->assertDatabaseCount('employee_shift_assignments', 2);
    }

    private function createEmployee(string $email = 'employee@example.test', string $code = 'EMP-100', bool $active = true): Employee
    {
        $department = Department::create(['name' => 'Stitching '.substr(md5($email), 0, 8)]);
        $user = $this->createUser(User::ROLE_EMPLOYEE, $email, $active);

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-01',
        ]);
    }

    private function createShift(string $name = 'Day shift', bool $active = true): Shift
    {
        return Shift::create([
            'name' => $name,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'late_grace_minutes' => 15,
            'is_active' => $active,
        ]);
    }

    private function createUser(string $role, string $email, bool $active = true): User
    {
        $user = new User;
        $user->name = ucfirst($role).' User';
        $user->email = $email;
        $user->password = Hash::make('Assignment-Management-Test-Password');
        $user->role = $role;
        $user->is_active = $active;
        $user->must_change_password = false;
        $user->save();

        return $user;
    }
}
