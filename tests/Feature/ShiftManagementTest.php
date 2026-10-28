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

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_shift_management_is_hr_admin_only(): void
    {
        $shift = $this->createShift();
        $index = route('hr-admin.shifts.index');
        $create = route('hr-admin.shifts.create');
        $store = route('hr-admin.shifts.store');
        $show = route('hr-admin.shifts.show', $shift);
        $edit = route('hr-admin.shifts.edit', $shift);
        $update = route('hr-admin.shifts.update', $shift);

        $this->get($index)->assertRedirect(route('login'));
        $this->get($create)->assertRedirect(route('login'));
        $this->post($store, [])->assertRedirect(route('login'));
        $this->get($show)->assertRedirect(route('login'));
        $this->get($edit)->assertRedirect(route('login'));
        $this->put($update, [])->assertRedirect(route('login'));
        $this->patch(route('hr-admin.shifts.deactivate', $shift))->assertRedirect(route('login'));
        $this->patch(route('hr-admin.shifts.activate', $shift))->assertRedirect(route('login'));

        foreach ([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR] as $role) {
            $this->actingAs($this->createUser($role, strtolower($role).'@example.test'));
            $this->get($index)->assertForbidden();
            $this->get($create)->assertForbidden();
            $this->post($store, [])->assertForbidden();
            $this->get($show)->assertForbidden();
            $this->get($edit)->assertForbidden();
            $this->put($update, [])->assertForbidden();
            $this->patch(route('hr-admin.shifts.deactivate', $shift))->assertForbidden();
            $this->patch(route('hr-admin.shifts.activate', $shift))->assertForbidden();
        }
    }

    public function test_hr_admin_can_create_view_and_edit_a_shift(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $input = [
            'name' => 'Evening line',
            'start_time' => '14:30',
            'end_time' => '22:30',
            'ends_next_day' => '0',
            'late_grace_minutes' => '10',
        ];

        $this->get(route('hr-admin.shifts.index'))->assertOk()->assertSee('Shifts');
        $this->get(route('hr-admin.shifts.create'))->assertOk()->assertSee('Late grace period');
        $this->post(route('hr-admin.shifts.store'), $input)->assertRedirect();

        $shift = Shift::query()->firstOrFail();
        $this->assertSame('Evening line', $shift->name);
        $this->assertSame('14:30', substr($shift->start_time, 0, 5));
        $this->assertSame('22:30', substr($shift->end_time, 0, 5));
        $this->assertFalse($shift->ends_next_day);
        $this->assertTrue($shift->is_active);

        $this->get(route('hr-admin.shifts.show', $shift))
            ->assertOk()
            ->assertSee('Evening line')
            ->assertSee('10 minutes');
        $this->put(route('hr-admin.shifts.update', $shift), [
            ...$input,
            'name' => 'Evening production line',
            'start_time' => '15:00',
            'ends_next_day' => '1',
            'is_active' => false,
        ])->assertRedirect(route('hr-admin.shifts.show', $shift));

        $shift->refresh();
        $this->assertSame('Evening production line', $shift->name);
        $this->assertSame('15:00', substr($shift->start_time, 0, 5));
        $this->assertTrue($shift->ends_next_day);
        $this->assertTrue($shift->is_active, 'Ordinary edit fields cannot change active status.');
    }

    public function test_shift_fields_are_validated_and_overnight_is_only_a_configuration_flag(): void
    {
        $this->actingAs($this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test'));
        $this->from(route('hr-admin.shifts.create'))
            ->post(route('hr-admin.shifts.store'), [
                'name' => '',
                'start_time' => '25:00',
                'end_time' => 'invalid',
                'ends_next_day' => '1',
                'late_grace_minutes' => '-1',
            ])
            ->assertSessionHasErrors(['name', 'start_time', 'end_time', 'late_grace_minutes']);

        $this->post(route('hr-admin.shifts.store'), [
            'name' => 'Overnight configuration',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'ends_next_day' => '1',
            'late_grace_minutes' => 15,
        ])->assertRedirect();

        $shift = Shift::query()->firstOrFail();
        $this->assertTrue($shift->ends_next_day);
        $this->get(route('hr-admin.shifts.show', $shift))
            ->assertSee('attendance punch processing currently rejects overnight shifts');
    }

    public function test_deactivation_and_reactivation_preserve_shift_assignments(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $shift = $this->createShift();
        $employee = $this->createEmployee();
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-12-31',
            'assigned_by_user_id' => $admin->id,
        ]);

        $this->get(route('hr-admin.shifts.show', $shift))
            ->assertOk()
            ->assertSee('Existing assignments and attendance history remain unchanged');
        $this->patch(route('hr-admin.shifts.deactivate', $shift))
            ->assertRedirect(route('hr-admin.shifts.show', $shift));

        $this->assertFalse($shift->fresh()->is_active);
        $this->assertSame($employee->id, $assignment->fresh()->employee_id);
        $this->assertSame($shift->id, $assignment->fresh()->shift_id);
        $this->assertSame('2025-01-01', $assignment->fresh()->effective_from->toDateString());
        $this->assertSame('2025-12-31', $assignment->fresh()->effective_to->toDateString());
        $this->patch(route('hr-admin.shifts.activate', $shift))
            ->assertRedirect(route('hr-admin.shifts.show', $shift));
        $this->assertTrue($shift->fresh()->is_active);
        $this->assertSame('2025-12-31', $assignment->fresh()->effective_to->toDateString());
    }

    public function test_editing_a_shift_with_assignments_requires_explicit_confirmation(): void
    {
        $admin = $this->createUser(User::ROLE_HR_ADMIN, 'admin@example.test');
        $this->actingAs($admin);
        $shift = $this->createShift();
        $employee = $this->createEmployee();
        $assignment = EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2025-01-01',
            'assigned_by_user_id' => $admin->id,
        ]);
        $input = [
            'name' => 'Renamed shift',
            'start_time' => '11:00',
            'end_time' => '19:00',
            'ends_next_day' => '0',
            'late_grace_minutes' => 15,
        ];

        $this->get(route('hr-admin.shifts.edit', $shift))
            ->assertOk()
            ->assertSee('future work dates for those assignments');
        $this->from(route('hr-admin.shifts.edit', $shift))
            ->put(route('hr-admin.shifts.update', $shift), $input)
            ->assertSessionHasErrors('confirm_assignment_impact');
        $this->assertSame('10:00:00', $shift->fresh()->start_time);
        $this->assertSame('2025-01-01', $assignment->fresh()->effective_from->toDateString());

        $this->put(route('hr-admin.shifts.update', $shift), $input + ['confirm_assignment_impact' => '1'])
            ->assertRedirect(route('hr-admin.shifts.show', $shift));
        $this->assertSame('11:00', substr($shift->fresh()->start_time, 0, 5));
        $this->assertSame('2025-01-01', $assignment->fresh()->effective_from->toDateString());
    }

    private function createShift(): Shift
    {
        return Shift::create([
            'name' => 'Day shift',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'ends_next_day' => false,
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);
    }

    private function createEmployee(): Employee
    {
        $department = Department::create(['name' => 'Stitching']);
        $user = $this->createUser(User::ROLE_EMPLOYEE, 'employee@example.test');

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-100',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2025-01-01',
        ]);
    }

    private function createUser(string $role, string $email): User
    {
        $user = new User;
        $user->name = ucfirst($role).' User';
        $user->email = $email;
        $user->password = Hash::make('Shift-Management-Test-Password');
        $user->role = $role;
        $user->is_active = true;
        $user->must_change_password = false;
        $user->save();

        return $user;
    }
}
