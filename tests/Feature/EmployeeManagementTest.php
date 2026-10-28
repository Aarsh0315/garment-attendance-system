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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private const TEMPORARY_PASSWORD = 'Temporary-Employee-456';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_and_non_hr_users_cannot_manage_employees(): void
    {
        $department = Department::create(['name' => 'Cutting']);
        $employee = $this->createEmployee($department);

        $this->get(route('hr-admin.employees.index'))->assertRedirect(route('login'));
        $this->get(route('hr-admin.employees.create'))->assertRedirect(route('login'));
        $this->get(route('hr-admin.employees.show', $employee))->assertRedirect(route('login'));
        $this->get(route('hr-admin.employees.edit', $employee))->assertRedirect(route('login'));
        $this->post(route('hr-admin.employees.store'), $this->employeeInput($department))->assertRedirect(route('login'));
        $this->patch(route('hr-admin.employees.activate', $employee))->assertRedirect(route('login'));

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR] as $role) {
            $user = $this->createUser($role, $role.'-denied@example.test');

            $this->actingAs($user)->get(route('hr-admin.employees.index'))->assertForbidden();
            $this->actingAs($user)->get(route('hr-admin.employees.create'))->assertForbidden();
            $this->actingAs($user)->get(route('hr-admin.employees.show', $employee))->assertForbidden();
            $this->actingAs($user)->get(route('hr-admin.employees.edit', $employee))->assertForbidden();
            $this->actingAs($user)->post(route('hr-admin.employees.store'), $this->employeeInput($department))->assertForbidden();
            $this->actingAs($user)->put(route('hr-admin.employees.update', $employee), [])->assertForbidden();
            $this->actingAs($user)->patch(route('hr-admin.employees.deactivate', $employee), [])->assertForbidden();
            $this->actingAs($user)->patch(route('hr-admin.employees.activate', $employee))->assertForbidden();
        }

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_hr_admin_can_search_employee_fields_and_see_a_clear_empty_state(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Quality Control']);
        $matchingEmployee = $this->createEmployee($department, 'asha@example.test', 'EMP-SEARCH-01');
        $matchingEmployee->user->name = 'Asha Sharma';
        $matchingEmployee->user->save();
        $matchingEmployee->designation = 'Quality Inspector';
        $matchingEmployee->save();
        $this->createEmployee(Department::create(['name' => 'Packing']), 'other@example.test', 'EMP-OTHER-02');

        foreach (['EMP-SEARCH', 'Asha Sharma', 'asha@example.test', 'Quality Control', 'Quality Inspector'] as $term) {
            $this->get(route('hr-admin.employees.index', ['search' => $term]))
                ->assertOk()
                ->assertSee('EMP-SEARCH-01')
                ->assertDontSee('EMP-OTHER-02');
        }

        $this->get(route('hr-admin.employees.index', ['search' => 'no-such-employee']))
            ->assertOk()
            ->assertSee('No employees match your search.');

        $this->get(route('hr-admin.employees.index', ['search' => str_repeat('x', 101)]))
            ->assertSessionHasErrors('search');
    }

    public function test_employee_list_displays_required_fields_and_deactivation_requires_confirmation(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Finishing']);
        $employee = $this->createEmployee($department, 'list@example.test', 'EMP-510');

        $this->get(route('hr-admin.employees.index'))
            ->assertOk()
            ->assertSee('Employee ID')
            ->assertSee('Status')
            ->assertSee('Joining date')
            ->assertSee('EMP-510')
            ->assertSee('list@example.test')
            ->assertSee('Finishing')
            ->assertSee('Operator')
            ->assertSee('2025-01-01')
            ->assertSee(route('hr-admin.employees.show', $employee))
            ->assertDontSee('Activate')
            ->assertDontSee('Deactivate');

        $this->get(route('hr-admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('name="effective_date"', false)
            ->assertSee('data-confirm=', false)
            ->assertSee('history will be retained')
            ->assertDontSee('Delete employee');
    }

    public function test_hr_admin_can_create_employee_account_and_profile_without_privilege_escalation(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Quality Control']);
        $input = array_replace($this->employeeInput($department), [
            'name' => '  Example Employee  ',
            'email' => '  EMPLOYEE@EXAMPLE.TEST  ',
            'employee_code' => '  emp-101  ',
            'password' => self::TEMPORARY_PASSWORD,
            'password_confirmation' => self::TEMPORARY_PASSWORD,
            'is_active' => false,
            'must_change_password' => false,
        ]);

        foreach ([User::ROLE_HR_ADMIN, User::ROLE_SUPERVISOR] as $submittedRole) {
            $input['role'] = $submittedRole;
            $this->post(route('hr-admin.employees.store'), $input)->assertRedirect();

            $employee = Employee::query()->where('employee_code', 'EMP-101')->firstOrFail();
            $user = $employee->user;

            $this->assertSame('Example Employee', $user->name);
            $this->assertSame('employee@example.test', $user->email);
            $this->assertSame('EMP-101', $employee->employee_code);
            $this->assertSame(User::ROLE_EMPLOYEE, $user->role);
            $this->assertTrue($user->is_active);
            $this->assertTrue($user->must_change_password);
            $this->assertTrue(Hash::check(self::TEMPORARY_PASSWORD, $user->password));
            $this->assertNotSame(self::TEMPORARY_PASSWORD, $user->password);

            $input['email'] = $submittedRole.'-employee@example.test';
            $input['employee_code'] = $submittedRole.'-102';
        }

        $this->get(route('hr-admin.employees.create'))
            ->assertOk()
            ->assertDontSee(self::TEMPORARY_PASSWORD);
        $this->assertDatabaseCount('employees', 2);
    }

    public function test_temporary_password_must_be_confirmed_and_at_least_twelve_characters(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Packing']);
        $input = array_replace($this->employeeInput($department), [
            'password' => 'short-pass',
            'password_confirmation' => 'short-pass',
        ]);

        $this->from(route('hr-admin.employees.create'))
            ->post(route('hr-admin.employees.store'), $input)
            ->assertSessionHasErrors('password');

        $this->assertNull(session()->get('_old_input.password'));
        $this->assertNull(session()->get('_old_input.password_confirmation'));
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 1);

        $input['password'] = self::TEMPORARY_PASSWORD;
        $input['password_confirmation'] = 'Different-Temporary-Password';

        $this->from(route('hr-admin.employees.create'))
            ->post(route('hr-admin.employees.store'), $input)
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_and_employee_code_duplicates_are_rejected_after_normalization(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Stitching']);
        $this->createEmployee($department, 'employee@example.test', 'EMP-700');

        $this->from(route('hr-admin.employees.create'))
            ->post(route('hr-admin.employees.store'), $this->employeeInput($department, [
                'email' => 'EMPLOYEE@EXAMPLE.TEST',
                'employee_code' => ' emp-700 ',
            ]))
            ->assertSessionHasErrors(['email', 'employee_code']);

        $secondEmployee = $this->createEmployee($department, 'second@example.test', 'EMP-701');
        $this->from(route('hr-admin.employees.edit', $secondEmployee))
            ->put(route('hr-admin.employees.update', $secondEmployee), $this->employeeInput($department, [
                'name' => $secondEmployee->user->name,
                'email' => 'EMPLOYEE@EXAMPLE.TEST',
                'employee_code' => ' emp-700 ',
            ]))
            ->assertSessionHasErrors(['email', 'employee_code']);

        $this->assertDatabaseCount('employees', 2);
        $this->assertDatabaseCount('users', 3);
    }

    public function test_creation_and_reassignment_require_active_departments(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $activeDepartment = Department::create(['name' => 'Finishing']);
        $inactiveDepartment = Department::create(['name' => 'Warehouse']);
        $inactiveDepartment->is_active = false;
        $inactiveDepartment->save();

        $this->from(route('hr-admin.employees.create'))
            ->post(route('hr-admin.employees.store'), $this->employeeInput($inactiveDepartment))
            ->assertSessionHasErrors('department_id');

        $employee = $this->createEmployee($activeDepartment);

        $this->from(route('hr-admin.employees.edit', $employee))
            ->put(route('hr-admin.employees.update', $employee), $this->employeeInput($inactiveDepartment, [
                'name' => $employee->user->name,
                'email' => $employee->user->email,
                'employee_code' => $employee->employee_code,
            ]))
            ->assertSessionHasErrors('department_id');

        $this->assertSame($activeDepartment->id, $employee->fresh()->department_id);
        $this->assertDatabaseCount('users', 2);

        $activeDepartment->is_active = false;
        $activeDepartment->save();

        $this->put(route('hr-admin.employees.update', $employee), $this->employeeInput($activeDepartment, [
            'name' => $employee->user->name,
            'email' => $employee->user->email,
            'employee_code' => $employee->employee_code,
            'designation' => 'Updated without reassignment',
        ]))->assertRedirect(route('hr-admin.employees.show', $employee));

        $this->assertSame('Updated without reassignment', $employee->fresh()->designation);
    }

    public function test_hr_admin_can_edit_profile_but_cannot_change_account_privileges(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Cutting']);
        $newDepartment = Department::create(['name' => 'Assembly']);
        $employee = $this->createEmployee($department, 'before@example.test', 'EMP-800');

        $this->get(route('hr-admin.employees.index'))->assertOk()->assertSee('EMP-800');
        $this->get(route('hr-admin.employees.show', $employee))->assertOk()->assertSee($employee->user->name);
        $this->get(route('hr-admin.employees.edit', $employee))->assertOk()->assertSee('Edit employee');

        $this->put(route('hr-admin.employees.update', $employee), [
            'name' => 'Updated Employee',
            'email' => ' UPDATED@EXAMPLE.TEST ',
            'employee_code' => ' emp-801 ',
            'department_id' => $newDepartment->id,
            'designation' => 'Quality Checker',
            'joining_date' => '2025-08-12',
            'phone' => '555-0100',
            'role' => User::ROLE_HR_ADMIN,
            'is_active' => false,
            'must_change_password' => false,
        ])->assertRedirect(route('hr-admin.employees.show', $employee));

        $employee->refresh();
        $employee->load('user');
        $this->assertSame('Updated Employee', $employee->user->name);
        $this->assertSame('updated@example.test', $employee->user->email);
        $this->assertSame('EMP-801', $employee->employee_code);
        $this->assertSame($newDepartment->id, $employee->department_id);
        $this->assertSame(User::ROLE_EMPLOYEE, $employee->user->role);
        $this->assertTrue($employee->user->is_active);
        $this->assertTrue($employee->user->must_change_password);
    }

    public function test_account_and_employee_creation_roll_back_together_if_profile_creation_fails(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Inspection']);
        DB::unprepared("CREATE TRIGGER fail_employee_profile BEFORE INSERT ON employees BEGIN SELECT RAISE(ABORT, 'simulated profile failure'); END");
        $this->withoutExceptionHandling();

        try {
            $this->post(route('hr-admin.employees.store'), $this->employeeInput($department));
            $this->fail('The simulated profile write should fail.');
        } catch (QueryException) {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('employees', 0);
        }
    }

    public function test_deactivation_closes_assignment_inclusively_disables_login_and_retains_history(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $department = Department::create(['name' => 'Quality']);
        $employee = $this->createEmployee($department, 'deactivate@example.test', 'EMP-900');
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-01-01',
        ]);

        $this->patch(route('hr-admin.employees.deactivate', $employee), [
            'effective_date' => '2025-09-30',
        ])->assertRedirect(route('hr-admin.employees.show', $employee));

        $this->assertFalse($employee->user->fresh()->is_active);
        $this->assertSame('2025-09-30', $assignment->fresh()->effective_to->toDateString());
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('employee_supervisor_assignments', ['id' => $assignment->id]);
        $this->assertSame('Employee deactivated. The profile and history were retained.', session('status'));

        Auth::logout();
        $this->post(route('login.store'), [
            'email' => 'deactivate@example.test',
            'password' => self::TEMPORARY_PASSWORD,
        ])->assertSessionHasErrors('email');
    }

    public function test_hr_admin_can_reactivate_existing_account_without_changing_profile_or_history(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $department = Department::create(['name' => 'Quality']);
        $employee = $this->createEmployee($department, 'reactivate@example.test', 'EMP-910');
        $user = $employee->user;
        $user->is_active = false;
        $user->save();

        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'reactivation-supervisor@example.test');
        $supervisorAssignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-01-01',
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
            'effective_from' => '2025-01-01',
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
        $event->actor_user_id = $user->id;
        $event->event_type = 'check_in';
        $event->outcome = 'accepted';
        $event->source = 'employee_web';
        $event->event_at = '2025-09-15 04:30:00';
        $event->save();

        $originalPassword = $user->password;
        $this->patch(route('hr-admin.employees.activate', $employee))
            ->assertRedirect(route('hr-admin.employees.show', $employee));

        $this->assertTrue($user->fresh()->is_active);
        $this->assertSame(User::ROLE_EMPLOYEE, $user->fresh()->role);
        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertSame('EMP-910', $employee->fresh()->employee_code);
        $this->assertSame($department->id, $employee->fresh()->department_id);
        $this->assertSame('Operator', $employee->fresh()->designation);
        $this->assertSame('2025-01-01', $supervisorAssignment->fresh()->effective_from->toDateString());
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
        $this->assertSame(1, EmployeeSupervisorAssignment::query()->where('employee_id', $employee->id)->count());
        $this->assertSame(1, EmployeeShiftAssignment::query()->where('employee_id', $employee->id)->count());
        $this->assertSame('Employee account activated. Existing profile and history were retained.', session('status'));
    }

    public function test_reactivating_an_active_employee_is_rejected_without_changes(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $employee = $this->createEmployee(Department::create(['name' => 'Packing']));
        $originalPassword = $employee->user->password;

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.activate', $employee))
            ->assertSessionHasErrors('employee');

        $this->assertTrue($employee->user->fresh()->is_active);
        $this->assertSame($originalPassword, $employee->user->fresh()->password);
        $this->assertDatabaseCount('employee_supervisor_assignments', 0);
        $this->assertDatabaseCount('employee_shift_assignments', 0);
    }

    public function test_inactive_employee_list_and_detail_show_activation_action(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $employee = $this->createEmployee(Department::create(['name' => 'Cutting']));
        $employee->user->is_active = false;
        $employee->user->save();

        $this->get(route('hr-admin.employees.index'))
            ->assertOk()
            ->assertSee('Status')
            ->assertSee(route('hr-admin.employees.show', $employee))
            ->assertDontSee('Activate')
            ->assertDontSee('Deactivate');
        $this->get(route('hr-admin.employees.show', $employee))
            ->assertOk()
            ->assertSee('Activate account')
            ->assertSee('data-confirm=', false)
            ->assertSee('No supervisor or shift assignment will be created.');
    }

    public function test_invalid_deactivation_date_does_not_partially_change_account_or_assignment(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Packing']);
        $employee = $this->createEmployee($department, 'date-check@example.test', 'EMP-901');
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-06-10',
        ]);

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => '2025-06-09',
            ])
            ->assertSessionHasErrors('effective_date');

        $this->assertTrue($employee->user->fresh()->is_active);
        $this->assertNull($assignment->fresh()->effective_to);
    }

    public function test_overlapping_assignment_history_blocks_deactivation_without_partial_updates(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Stitching']);
        $employee = $this->createEmployee($department, 'overlap@example.test', 'EMP-902');
        $firstSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor-one@example.test');
        $secondSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor-two@example.test');
        $first = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $firstSupervisor->id,
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-07-01',
        ]);
        $second = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $secondSupervisor->id,
            'effective_from' => '2025-06-15',
        ]);

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => '2025-08-01',
            ])
            ->assertSessionHasErrors('effective_date');

        $this->assertTrue($employee->user->fresh()->is_active);
        $this->assertSame('2025-07-01', $first->fresh()->effective_to->toDateString());
        $this->assertNull($second->fresh()->effective_to);
    }

    public function test_invalid_date_format_is_rejected_before_deactivation(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $employee = $this->createEmployee(Department::create(['name' => 'Warehouse']));

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors('effective_date');

        $this->assertTrue($employee->user->fresh()->is_active);
    }

    public function test_invalid_assignment_date_range_blocks_deactivation_without_partial_updates(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Warehouse']);
        $employee = $this->createEmployee($department, 'invalid-range@example.test', 'EMP-903');
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-08-10',
            'effective_to' => '2025-08-09',
        ]);

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => '2025-09-01',
            ])
            ->assertSessionHasErrors('effective_date');

        $this->assertTrue($employee->user->fresh()->is_active);
        $this->assertSame('2025-08-09', $assignment->fresh()->effective_to->toDateString());
    }

    public function test_assignment_closure_rolls_back_if_account_deactivation_fails(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $department = Department::create(['name' => 'Finishing']);
        $employee = $this->createEmployee($department, 'atomic-deactivation@example.test', 'EMP-904');
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-01-01',
        ]);
        DB::unprepared("CREATE TRIGGER fail_employee_deactivation BEFORE UPDATE ON users WHEN OLD.email = 'atomic-deactivation@example.test' BEGIN SELECT RAISE(ABORT, 'simulated account update failure'); END");
        $this->withoutExceptionHandling();

        try {
            $this->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => '2025-09-30',
            ]);
            $this->fail('The simulated account update should fail.');
        } catch (QueryException) {
            $this->assertTrue($employee->user->fresh()->is_active);
            $this->assertNull($assignment->fresh()->effective_to);
        }
    }

    public function test_deactivation_date_cannot_precede_the_joining_date(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $employee = $this->createEmployee(Department::create(['name' => 'Cutting']), 'joining-date@example.test', 'EMP-905');

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => '2024-12-31',
            ])
            ->assertSessionHasErrors('effective_date');

        $this->assertTrue($employee->user->fresh()->is_active);
    }

    /** @return array<string, mixed> */
    private function employeeInput(Department $department, array $overrides = []): array
    {
        return array_replace([
            'name' => 'Test Employee',
            'email' => 'test.employee@example.test',
            'employee_code' => 'EMP-100',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-01',
            'phone' => null,
            'password' => self::TEMPORARY_PASSWORD,
            'password_confirmation' => self::TEMPORARY_PASSWORD,
        ], $overrides);
    }

    private function createEmployee(
        Department $department,
        string $email = 'employee@example.test',
        string $employeeCode = 'EMP-001',
    ): Employee {
        $user = $this->createUser(User::ROLE_EMPLOYEE, $email);
        $user->must_change_password = true;
        $user->save();

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => $employeeCode,
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-01',
        ]);
    }

    private function createUser(string $role, string $email): User
    {
        $user = new User;
        $user->name = ucfirst($role).' Test User';
        $user->email = $email;
        $user->password = Hash::make(self::TEMPORARY_PASSWORD);
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
