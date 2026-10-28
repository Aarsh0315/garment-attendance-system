<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeShiftAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeShiftAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_valid_assignment(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();

        $assignment = app(EmployeeShiftAssignmentService::class)->assign(
            $employee,
            $shift,
            '2026-01-01',
            '2026-01-31',
            $admin,
        );

        $this->assertDatabaseHas('employee_shift_assignments', [
            'id' => $assignment->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'assigned_by_user_id' => $admin->id,
        ]);
        $this->assertSame('2026-01-01', $assignment->fresh()->effective_from->toDateString());
        $this->assertSame('2026-01-31', $assignment->fresh()->effective_to->toDateString());
    }

    public function test_assignment_service_rejects_inactive_employee_or_shift(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $service = app(EmployeeShiftAssignmentService::class);

        $shift->is_active = false;
        $shift->save();
        try {
            $service->assign($employee, $shift, '2026-01-01', null, $admin);
            $this->fail('An inactive shift must not receive new assignments.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('shift_id', $exception->errors());
        }

        $shift->is_active = true;
        $shift->save();
        $employee->user->is_active = false;
        $employee->user->save();
        try {
            $service->assign($employee, $shift, '2026-01-01', null, $admin);
            $this->fail('An inactive employee must not receive a new assignment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('employee_id', $exception->errors());
        }

        $this->assertDatabaseCount('employee_shift_assignments', 0);
    }

    public function test_assignment_start_and_end_dates_are_inclusive(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $service = app(EmployeeShiftAssignmentService::class);
        $assignment = $service->assign($employee, $shift, '2026-01-10', '2026-01-20', $admin);

        $this->assertTrue($service->forWorkDate($employee, '2026-01-10')->is($assignment));
        $this->assertTrue($service->forWorkDate($employee, '2026-01-20')->is($assignment));
    }

    public function test_null_end_date_is_an_ongoing_assignment(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $service = app(EmployeeShiftAssignmentService::class);
        $assignment = $service->assign($employee, $shift, '2026-01-10', null, $admin);

        $this->assertNull($assignment->effective_to);
        $this->assertTrue($service->forWorkDate($employee, '2030-12-31')->is($assignment));
    }

    public function test_it_rejects_invalid_date_ranges_and_malformed_dates_without_writing(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $service = app(EmployeeShiftAssignmentService::class);

        try {
            $service->assign($employee, $shift, '2026-02-01', '2026-01-31', $admin);
            $this->fail('An end date earlier than the start date should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('effective_to', $exception->errors());
        }

        try {
            $service->assign($employee, $shift, '2026-02-30', null, $admin);
            $this->fail('An invalid calendar date should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('effective_from', $exception->errors());
        }

        $this->assertDatabaseCount('employee_shift_assignments', 0);
    }

    public function test_it_rejects_a_new_range_that_starts_inside_an_existing_range(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $original = $this->existingAssignment($employee, $shift, $admin, '2026-01-10', '2026-01-20');

        $this->assertOverlapRejected($employee, $shift, $admin, '2026-01-15', '2026-01-25');
        $this->assertOriginalHistoryUnchanged($original);
    }

    public function test_it_rejects_a_new_range_that_contains_an_existing_range(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $original = $this->existingAssignment($employee, $shift, $admin, '2026-01-10', '2026-01-20');

        $this->assertOverlapRejected($employee, $shift, $admin, '2026-01-01', '2026-01-31');
        $this->assertOriginalHistoryUnchanged($original);
    }

    public function test_same_day_boundary_is_an_inclusive_overlap(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $original = $this->existingAssignment($employee, $shift, $admin, '2026-01-01', '2026-01-31');

        $this->assertOverlapRejected($employee, $shift, $admin, '2026-01-31', '2026-02-10');
        $this->assertOriginalHistoryUnchanged($original);
    }

    public function test_adjacent_periods_are_accepted_without_a_boundary_overlap(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $this->existingAssignment($employee, $shift, $admin, '2026-01-01', '2026-01-31');

        $next = app(EmployeeShiftAssignmentService::class)->assign(
            $employee,
            $shift,
            '2026-02-01',
            null,
            $admin,
        );

        $this->assertSame('2026-02-01', $next->effective_from->toDateString());
    }

    public function test_gaps_are_allowed_but_no_assignment_is_returned_for_a_gap_work_date(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $this->existingAssignment($employee, $shift, $admin, '2026-01-01', '2026-01-10');
        app(EmployeeShiftAssignmentService::class)->assign($employee, $shift, '2026-01-20', '2026-01-31', $admin);

        $this->expectException(ValidationException::class);
        app(EmployeeShiftAssignmentService::class)->forWorkDate($employee, '2026-01-15');
    }

    public function test_failed_overlap_validation_leaves_existing_assignment_history_unchanged(): void
    {
        [$employee, $shift, $admin] = $this->setupAssignmentContext();
        $original = $this->existingAssignment($employee, $shift, $admin, '2026-01-01', null);

        $this->assertOverlapRejected($employee, $shift, $admin, '2026-02-01', '2026-02-28');
        $this->assertOriginalHistoryUnchanged($original);
    }

    /** @return array{Employee, Shift, User} */
    private function setupAssignmentContext(): array
    {
        $department = Department::create(['name' => 'Stitching']);
        $employeeUser = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-001',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_HR_ADMIN,
            'is_active' => true,
        ]);
        $shift = Shift::create([
            'name' => 'Day Shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'ends_next_day' => false,
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);

        return [$employee, $shift, $admin];
    }

    private function existingAssignment(
        Employee $employee,
        Shift $shift,
        User $admin,
        string $effectiveFrom,
        ?string $effectiveTo,
    ): EmployeeShiftAssignment {
        return EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'assigned_by_user_id' => $admin->id,
        ]);
    }

    private function assertOverlapRejected(
        Employee $employee,
        Shift $shift,
        User $admin,
        string $effectiveFrom,
        ?string $effectiveTo,
    ): void {
        try {
            app(EmployeeShiftAssignmentService::class)->assign(
                $employee,
                $shift,
                $effectiveFrom,
                $effectiveTo,
                $admin,
            );
            $this->fail('An overlapping assignment should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('effective_from', $exception->errors());
        }
    }

    private function assertOriginalHistoryUnchanged(EmployeeShiftAssignment $original): void
    {
        $this->assertDatabaseCount('employee_shift_assignments', 1);
        $unchanged = $original->fresh();
        $this->assertSame($original->effective_from->toDateString(), $unchanged->effective_from->toDateString());
        $this->assertSame($original->effective_to?->toDateString(), $unchanged->effective_to?->toDateString());
    }
}
