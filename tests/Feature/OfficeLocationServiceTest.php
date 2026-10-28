<?php

namespace Tests\Feature;

use App\Models\AttendanceEvent;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\OfficeLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfficeLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_valid_location_inactive_by_default(): void
    {
        $location = $this->service()->create($this->validDetails());

        $this->assertFalse($location->is_active);
        $this->assertDatabaseHas('office_locations', [
            'id' => $location->id,
            'name' => 'Temporary Test Location',
            'environment' => 'development',
            'is_active' => false,
        ]);
        $serviceLocation = $this->service()->findOrFail($location->id);
        $this->assertTrue($location->is($serviceLocation));
        $this->assertCount(1, $this->service()->all());
    }

    public function test_it_rejects_out_of_range_coordinates_invalid_radius_and_missing_fields(): void
    {
        $invalidInputs = [
            ['latitude' => '90.0000001'],
            ['longitude' => '-180.0000001'],
            ['latitude' => '12.12345678'],
            ['radius_meters' => 0],
            ['radius_meters' => -1],
            ['name' => ''],
            ['environment' => str_repeat('x', 17)],
        ];

        foreach ($invalidInputs as $invalid) {
            try {
                $this->service()->create(array_merge($this->validDetails(), $invalid));
                $this->fail('Invalid location values should be rejected.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('office_locations', 0);
            }
        }

        $missing = $this->validDetails();
        unset($missing['latitude']);

        try {
            $this->service()->create($missing);
            $this->fail('Required location fields should be rejected when missing.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('office_locations', 0);
        }
    }

    public function test_it_activates_a_location_when_its_environment_has_no_active_location(): void
    {
        $location = $this->service()->create($this->validDetails());

        $activated = $this->service()->activate($location);

        $this->assertTrue($activated->is_active);
        $this->assertDatabaseHas('office_locations', ['id' => $location->id, 'is_active' => true]);
    }

    public function test_it_rejects_activation_when_another_location_is_active_in_the_same_environment(): void
    {
        $service = $this->service();
        $first = $service->create($this->validDetails());
        $second = $service->create(array_merge($this->validDetails(), ['name' => 'Second Location']));
        $service->activate($first);

        try {
            $service->activate($second);
            $this->fail('Only one location may be active per environment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }

        $this->assertDatabaseHas('office_locations', ['id' => $first->id, 'is_active' => true]);
        $this->assertDatabaseHas('office_locations', ['id' => $second->id, 'is_active' => false]);
    }

    public function test_database_unique_conflict_is_reported_clearly_and_rolls_back(): void
    {
        $service = $this->service();
        $target = $service->create($this->validDetails());

        OfficeLocation::updating(function (OfficeLocation $location): void {
            if ($location->isDirty('is_active') && $location->is_active) {
                OfficeLocation::create([
                    'name' => 'Concurrent Location',
                    'environment' => $location->environment,
                    'latitude' => '19.0000000',
                    'longitude' => '72.0000000',
                    'radius_meters' => 100,
                    'is_active' => true,
                ]);
            }
        });

        try {
            try {
                $service->activate($target);
                $this->fail('The database unique constraint should reject the concurrent activation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('environment', $exception->errors());
            }
        } finally {
            OfficeLocation::flushEventListeners();
        }

        $this->assertDatabaseCount('office_locations', 1);
        $this->assertDatabaseHas('office_locations', ['id' => $target->id, 'is_active' => false]);
    }

    public function test_active_locations_in_different_environments_are_allowed(): void
    {
        $service = $this->service();
        $development = $service->activate($service->create($this->validDetails()));
        $testing = $service->activate($service->create(array_merge(
            $this->validDetails(),
            ['name' => 'Test Environment Location', 'environment' => 'testing'],
        )));

        $this->assertTrue($development->is_active);
        $this->assertTrue($testing->is_active);
    }

    public function test_an_active_location_can_move_to_an_unoccupied_environment(): void
    {
        $service = $this->service();
        $location = $service->activate($service->create($this->validDetails()));

        $updated = $service->update($location, ['environment' => ' Testing ']);

        $this->assertSame('testing', $updated->environment);
        $this->assertTrue($updated->is_active);
        $this->assertTrue($service->activate($service->create(array_merge(
            $this->validDetails(),
            ['name' => 'New Development Location'],
        )))->is_active);
    }

    public function test_it_deactivates_a_location_without_removing_it(): void
    {
        $service = $this->service();
        $location = $service->activate($service->create($this->validDetails()));

        $deactivated = $service->deactivate($location);

        $this->assertFalse($deactivated->is_active);
        $this->assertDatabaseHas('office_locations', ['id' => $location->id, 'is_active' => false]);
    }

    public function test_updating_location_details_does_not_change_active_status(): void
    {
        $service = $this->service();
        $location = $service->activate($service->create($this->validDetails()));

        $updated = $service->update($location, [
            'name' => 'Updated Location',
            'latitude' => '19.0760000',
            'longitude' => '72.8777000',
            'radius_meters' => 150,
        ]);

        $this->assertTrue($updated->is_active);
        $this->assertSame('Updated Location', $updated->name);
        $this->assertSame('19.0760000', $updated->latitude);
        $this->assertSame(150, $updated->radius_meters);
    }

    public function test_detail_update_cannot_change_activation_status_and_failure_is_atomic(): void
    {
        $service = $this->service();
        $active = $service->activate($service->create($this->validDetails()));
        $otherEnvironment = $service->activate($service->create(array_merge(
            $this->validDetails(),
            ['name' => 'Other Environment', 'environment' => 'testing'],
        )));

        try {
            $service->update($active, ['name' => 'Must not persist', 'is_active' => false]);
            $this->fail('A detail update must not change activation status.');
        } catch (ValidationException) {
            $this->assertSame('Temporary Test Location', $service->findOrFail($active->id)->name);
        }

        try {
            $service->update($active, ['name' => 'Must also roll back', 'environment' => 'testing']);
            $this->fail('Changing an active location into an occupied environment should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }

        $unchanged = $service->findOrFail($active->id);
        $this->assertSame('Temporary Test Location', $unchanged->name);
        $this->assertSame('development', $unchanged->environment);
        $this->assertTrue($unchanged->is_active);
        $this->assertTrue($otherEnvironment->is_active);
    }

    public function test_location_changes_preserve_linked_attendance_event_history(): void
    {
        $service = $this->service();
        $location = $service->activate($service->create($this->validDetails()));
        [$employee, $actor] = $this->createEmployee();
        $event = new AttendanceEvent;
        $event->employee_id = $employee->id;
        $event->attendance_record_id = null;
        $event->actor_user_id = $actor->id;
        $event->office_location_id = $location->id;
        $event->event_type = 'check_in';
        $event->outcome = 'rejected';
        $event->source = 'employee_web';
        $event->event_at = '2026-10-28 04:30:00';
        $event->latitude = '19.0000000';
        $event->longitude = '72.0000000';
        $event->accuracy_meters = '12.50';
        $event->distance_meters = '25.00';
        $event->rejection_code = 'outside_boundary';
        $event->save();

        $service->update($location, [
            'name' => 'Renamed Location',
            'latitude' => '20.0000000',
            'radius_meters' => 200,
        ]);
        $service->deactivate($location);

        $this->assertDatabaseHas('office_locations', ['id' => $location->id, 'name' => 'Renamed Location', 'is_active' => false]);
        $this->assertDatabaseHas('attendance_events', [
            'id' => $event->id,
            'office_location_id' => $location->id,
            'latitude' => '19.0000000',
            'longitude' => '72.0000000',
            'distance_meters' => '25.00',
            'rejection_code' => 'outside_boundary',
        ]);
        $this->assertSame(1, $service->all()->count());
        $this->assertTrue($service->findOrFail($location->id)->exists);
    }

    private function service(): OfficeLocationService
    {
        return app(OfficeLocationService::class);
    }

    /** @return array{name: string, environment: string, latitude: string, longitude: string, radius_meters: int} */
    private function validDetails(): array
    {
        return [
            'name' => 'Temporary Test Location',
            'environment' => 'development',
            // Deliberately generic test coordinates; this does not configure a factory location.
            'latitude' => '19.0000000',
            'longitude' => '72.0000000',
            'radius_meters' => 100,
        ];
    }

    /** @return array{Employee, User} */
    private function createEmployee(): array
    {
        $department = Department::create(['name' => 'Stitching']);
        $actor = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'user_id' => $actor->id,
            'employee_code' => 'EMP-001',
            'department_id' => $department->id,
            'designation' => 'Operator',
            'joining_date' => '2026-01-01',
        ]);

        return [$employee, $actor];
    }
}
