<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSupervisorAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_two_tables_and_required_columns_are_created(): void
    {
        $this->assertTrue($this->app['db']->getSchemaBuilder()->hasTable('departments'));
        $this->assertTrue($this->app['db']->getSchemaBuilder()->hasTable('employees'));
        $this->assertTrue($this->app['db']->getSchemaBuilder()->hasTable('employee_supervisor_assignments'));
        $this->assertTrue($this->app['db']->getSchemaBuilder()->hasColumn('users', 'must_change_password'));

        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'normalized_name', 'is_active', 'created_at', 'updated_at'],
            $this->app['db']->getSchemaBuilder()->getColumnListing('departments'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'user_id', 'employee_code', 'department_id', 'designation', 'joining_date', 'phone', 'created_at', 'updated_at'],
            $this->app['db']->getSchemaBuilder()->getColumnListing('employees'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'employee_id', 'supervisor_user_id', 'effective_from', 'effective_to', 'created_at', 'updated_at'],
            $this->app['db']->getSchemaBuilder()->getColumnListing('employee_supervisor_assignments'),
        );
    }

    public function test_department_names_are_trimmed_and_case_insensitive_unique(): void
    {
        $department = Department::create(['name' => '  Quality Control  ']);

        $this->assertSame('Quality Control', $department->name);
        $this->assertSame('quality control', $department->normalized_name);
        $this->assertTrue($department->fresh()->is_active);

        try {
            Department::create(['name' => 'quality control']);
            $this->fail('A case-insensitive duplicate department name should violate the unique constraint.');
        } catch (QueryException) {
            $this->assertDatabaseCount('departments', 1);
        }
    }

    public function test_employee_codes_are_trimmed_uppercased_and_unique(): void
    {
        $department = Department::create(['name' => 'Assembly']);
        $user = $this->createUser('worker@example.test');
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => '  ab-123  ',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-15',
        ]);

        $this->assertSame('AB-123', $employee->employee_code);

        $duplicateUser = $this->createUser('second.worker@example.test');

        try {
            Employee::create([
                'user_id' => $duplicateUser->id,
                'employee_code' => 'ab-123',
                'department_id' => $department->id,
                'designation' => 'Operator',
                'joining_date' => '2025-02-01',
            ]);
            $this->fail('A normalized duplicate employee code should violate the unique constraint.');
        } catch (QueryException) {
            $this->assertDatabaseCount('employees', 1);
        }
    }

    public function test_a_user_can_have_only_one_employee_profile(): void
    {
        $department = Department::create(['name' => 'Finishing']);
        $user = $this->createUser('single-profile@example.test');

        Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-300',
            'department_id' => $department->id,
            'designation' => 'Inspector',
            'joining_date' => '2025-05-01',
        ]);

        try {
            Employee::create([
                'user_id' => $user->id,
                'employee_code' => 'EMP-301',
                'department_id' => $department->id,
                'designation' => 'Inspector',
                'joining_date' => '2025-05-01',
            ]);
            $this->fail('A user should not have more than one employee profile.');
        } catch (QueryException) {
            $this->assertDatabaseCount('employees', 1);
        }
    }

    public function test_existing_users_default_to_not_requiring_a_password_change(): void
    {
        $user = $this->createUser('existing@example.test');

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertIsBool($user->fresh()->must_change_password);
    }

    public function test_user_employee_department_and_supervisor_assignment_relationships_work(): void
    {
        $department = Department::create(['name' => 'Cutting']);
        $employeeUser = $this->createUser('employee@example.test');
        $supervisor = $this->createUser('supervisor@example.test', User::ROLE_SUPERVISOR);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-100',
            'department_id' => $department->id,
            'designation' => 'Cutter',
            'joining_date' => '2025-03-01',
            'phone' => null,
        ]);
        $assignment = EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-03-01',
            'effective_to' => '2025-03-31',
        ]);

        $this->assertTrue($employeeUser->employee->is($employee));
        $this->assertTrue($employee->user->is($employeeUser));
        $this->assertTrue($employee->department->is($department));
        $this->assertTrue($department->employees->contains($employee));
        $this->assertTrue($employee->supervisorAssignments->first()->is($assignment));
        $this->assertTrue($assignment->supervisor->is($supervisor));
        $this->assertSame('2025-03-31', $assignment->effective_to->toDateString());
    }

    public function test_referenced_records_cannot_be_deleted(): void
    {
        $department = Department::create(['name' => 'Packing']);
        $employeeUser = $this->createUser('employee@example.test');
        $supervisor = $this->createUser('supervisor@example.test', User::ROLE_SUPERVISOR);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-200',
            'department_id' => $department->id,
            'designation' => 'Packer',
            'joining_date' => '2025-04-01',
        ]);
        EmployeeSupervisorAssignment::create([
            'employee_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'effective_from' => '2025-04-01',
        ]);

        try {
            $department->delete();
            $this->fail('A referenced department should not be deletable.');
        } catch (QueryException) {
            $this->assertDatabaseHas('departments', ['id' => $department->id]);
        }

        try {
            $employee->delete();
            $this->fail('An employee with assignment history should not be deletable.');
        } catch (QueryException) {
            $this->assertDatabaseHas('employees', ['id' => $employee->id]);
            $this->assertDatabaseCount('employee_supervisor_assignments', 1);
        }
    }

    private function createUser(string $email, string $role = User::ROLE_EMPLOYEE): User
    {
        $user = new User;
        $user->name = 'Test User';
        $user->email = $email;
        $user->password = 'test-password';
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
