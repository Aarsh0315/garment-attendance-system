<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSupervisorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisorAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Assignment-Test-Password-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_only_hr_admins_can_view_or_change_assignment_history(): void
    {
        $employee = $this->createEmployee();
        $index = route('hr-admin.employees.supervisor-assignments.index', $employee);
        $create = route('hr-admin.employees.supervisor-assignments.create', $employee);
        $store = route('hr-admin.employees.supervisor-assignments.store', $employee);

        $this->get($index)->assertRedirect(route('login'));
        $this->get($create)->assertRedirect(route('login'));
        $this->post($store, [])->assertRedirect(route('login'));

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR] as $role) {
            $user = $this->createUser($role, strtolower($role).'-denied@example.test');
            $payload = $role === User::ROLE_SUPERVISOR
                ? ['supervisor_user_id' => $user->id, 'effective_from' => '2025-02-01']
                : [];

            $this->actingAs($user)->get($index)->assertForbidden();
            $this->actingAs($user)->get($create)->assertForbidden();
            $this->actingAs($user)->post($store, $payload)->assertForbidden();
        }

        $this->assertDatabaseCount('employee_supervisor_assignments', 0);
    }

    public function test_hr_admin_can_view_history_and_create_a_first_assignment(): void
    {
        $employee = $this->createEmployee();
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->get(route('hr-admin.employees.supervisor-assignments.index', $employee))
            ->assertOk()
            ->assertSee('No supervisor assignments have been recorded.');

        $this->get(route('hr-admin.employees.supervisor-assignments.create', $employee))
            ->assertOk()
            ->assertSee('This employee has no previous assignment.');

        $this->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-02-01',
        ])->assertRedirect(route('hr-admin.employees.supervisor-assignments.index', $employee));

        $assignment = EmployeeSupervisorAssignment::query()->firstOrFail();
        $this->assertSame($employee->id, $assignment->employee_id);
        $this->assertSame($supervisor->id, $assignment->supervisor_user_id);
        $this->assertSame('2025-02-01', $assignment->effective_from->toDateString());
        $this->assertNull($assignment->effective_to);

        $this->get(route('hr-admin.employees.supervisor-assignments.index', $employee))
            ->assertOk()
            ->assertSee($supervisor->name)
            ->assertSee('2025-02-01')
            ->assertSee('Ongoing');
    }

    public function test_inactive_or_non_supervisor_accounts_cannot_be_assigned(): void
    {
        $employee = $this->createEmployee();
        $hrAdmin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $inactiveSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'inactive-supervisor@example.test', false);
        $employeeAccount = $this->createUser(User::ROLE_EMPLOYEE, 'another-employee@example.test');
        $this->actingAs($hrAdmin);

        foreach ([$inactiveSupervisor, $employeeAccount] as $invalidSupervisor) {
            $this->from(route('hr-admin.employees.supervisor-assignments.create', $employee))
                ->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
                    'supervisor_user_id' => $invalidSupervisor->id,
                    'effective_from' => '2025-02-01',
                ])
                ->assertSessionHasErrors('supervisor_user_id');
        }

        $this->assertDatabaseCount('employee_supervisor_assignments', 0);
    }

    public function test_replacing_an_ongoing_assignment_closes_the_previous_day_inclusively(): void
    {
        $employee = $this->createEmployee();
        $firstSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'first@example.test');
        $replacementSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'replacement@example.test');
        $first = $this->createAssignment($employee, $firstSupervisor, '2025-01-01');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
            'supervisor_user_id' => $replacementSupervisor->id,
            'effective_from' => '2025-05-01',
        ])->assertRedirect();

        $this->assertSame('2025-04-30', $first->fresh()->effective_to->toDateString());
        $replacement = EmployeeSupervisorAssignment::query()->where('supervisor_user_id', $replacementSupervisor->id)->firstOrFail();
        $this->assertSame('2025-05-01', $replacement->effective_from->toDateString());
        $this->assertNull($replacement->effective_to);
        $this->assertSame(
            $first->fresh()->effective_to->copy()->addDay()->toDateString(),
            $replacement->effective_from->toDateString(),
        );
    }

    public function test_replacement_date_that_would_overlap_or_rewrite_history_is_rejected(): void
    {
        $employee = $this->createEmployee();
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $current = $this->createAssignment($employee, $supervisor, '2025-03-10');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        foreach (['2025-03-10', '2025-03-09'] as $invalidStart) {
            $this->from(route('hr-admin.employees.supervisor-assignments.create', $employee))
                ->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
                    'supervisor_user_id' => $this->createUser(User::ROLE_SUPERVISOR, uniqid('supervisor-', true).'@example.test')->id,
                    'effective_from' => $invalidStart,
                ])
                ->assertSessionHasErrors('effective_from');
        }

        $this->assertNull($current->fresh()->effective_to);
        $this->assertDatabaseCount('employee_supervisor_assignments', 1);
    }

    public function test_assignment_after_a_closed_period_must_start_on_the_following_day(): void
    {
        $employee = $this->createEmployee();
        $firstSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'first@example.test');
        $nextSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'next@example.test');
        $previous = $this->createAssignment($employee, $firstSupervisor, '2025-01-01', '2025-01-31');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->from(route('hr-admin.employees.supervisor-assignments.create', $employee))
            ->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
                'supervisor_user_id' => $nextSupervisor->id,
                'effective_from' => '2025-02-02',
            ])
            ->assertSessionHasErrors('effective_from');

        $this->assertSame('2025-01-31', $previous->fresh()->effective_to->toDateString());
        $this->assertDatabaseCount('employee_supervisor_assignments', 1);

        $this->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
            'supervisor_user_id' => $nextSupervisor->id,
            'effective_from' => '2025-02-01',
        ])->assertRedirect();

        $this->assertDatabaseCount('employee_supervisor_assignments', 2);
    }

    public function test_corrupt_overlapping_or_invalid_history_blocks_assignment_without_writes(): void
    {
        $employee = $this->createEmployee();
        $firstSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'first@example.test');
        $secondSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'second@example.test');
        $newSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'new@example.test');
        $first = $this->createAssignment($employee, $firstSupervisor, '2025-01-01', '2025-04-01');
        $this->createAssignment($employee, $secondSupervisor, '2025-03-01');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->from(route('hr-admin.employees.supervisor-assignments.create', $employee))
            ->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
                'supervisor_user_id' => $newSupervisor->id,
                'effective_from' => '2025-05-01',
            ])
            ->assertSessionHasErrors('effective_from');

        $this->assertSame('2025-04-01', $first->fresh()->effective_to->toDateString());
        $this->assertDatabaseCount('employee_supervisor_assignments', 2);
    }

    public function test_invalid_existing_date_range_blocks_assignment_without_writes(): void
    {
        $employee = $this->createEmployee();
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $newSupervisor = $this->createUser(User::ROLE_SUPERVISOR, 'new@example.test');
        $invalid = $this->createAssignment($employee, $supervisor, '2025-04-10', '2025-04-09');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->from(route('hr-admin.employees.supervisor-assignments.create', $employee))
            ->post(route('hr-admin.employees.supervisor-assignments.store', $employee), [
                'supervisor_user_id' => $newSupervisor->id,
                'effective_from' => '2025-04-11',
            ])
            ->assertSessionHasErrors('effective_from');

        $this->assertSame('2025-04-09', $invalid->fresh()->effective_to->toDateString());
        $this->assertDatabaseCount('employee_supervisor_assignments', 1);
    }

    public function test_deactivation_closes_current_assignment_on_the_selected_inclusive_date(): void
    {
        $employee = $this->createEmployee();
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = $this->createAssignment($employee, $supervisor, '2025-01-01');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->patch(route('hr-admin.employees.deactivate', $employee), [
            'effective_date' => '2025-09-30',
        ])->assertRedirect(route('hr-admin.employees.show', $employee));

        $this->assertFalse($employee->user->fresh()->is_active);
        $this->assertSame('2025-09-30', $assignment->fresh()->effective_to->toDateString());
        $this->assertDatabaseHas('employee_supervisor_assignments', ['id' => $assignment->id]);
    }

    public function test_invalid_deactivation_does_not_change_user_or_assignment(): void
    {
        $employee = $this->createEmployee();
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = $this->createAssignment($employee, $supervisor, '2025-06-10');
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->from(route('hr-admin.employees.show', $employee))
            ->patch(route('hr-admin.employees.deactivate', $employee), [
                'effective_date' => '2025-06-09',
            ])
            ->assertSessionHasErrors('effective_date');

        $this->assertTrue($employee->user->fresh()->is_active);
        $this->assertNull($assignment->fresh()->effective_to);
    }

    public function test_deactivated_employee_assignment_history_is_read_only_and_retained(): void
    {
        $employee = $this->createEmployee();
        $supervisor = $this->createUser(User::ROLE_SUPERVISOR, 'supervisor@example.test');
        $assignment = $this->createAssignment($employee, $supervisor, '2025-01-01', '2025-09-30');
        $employee->user->is_active = false;
        $employee->user->save();
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));

        $this->get(route('hr-admin.employees.supervisor-assignments.index', $employee))
            ->assertOk()
            ->assertSee('read-only')
            ->assertSee($supervisor->name);
        $this->get(route('hr-admin.employees.supervisor-assignments.create', $employee))
            ->assertRedirect(route('hr-admin.employees.supervisor-assignments.index', $employee));

        $this->assertDatabaseHas('employee_supervisor_assignments', ['id' => $assignment->id]);
    }

    private function createEmployee(): Employee
    {
        $user = $this->createUser(User::ROLE_EMPLOYEE, 'employee@example.test');

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-400',
            'department_id' => Department::create(['name' => 'Assembly'])->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-01',
        ]);
    }

    private function createAssignment(
        Employee $employee,
        User $supervisor,
        string $effectiveFrom,
        ?string $effectiveTo = null,
    ): EmployeeSupervisorAssignment {
        return EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
        ]);
    }

    private function createUser(string $role, string $email, bool $isActive = true): User
    {
        return User::factory()->create([
            'role' => $role,
            'email' => $email,
            'is_active' => $isActive,
            'password' => Hash::make(self::PASSWORD),
            'must_change_password' => false,
        ]);
    }
}
