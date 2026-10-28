<?php

namespace Tests\Feature;

use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\OfficeLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeLocationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_and_non_hr_roles_cannot_access_location_management(): void
    {
        $location = app(OfficeLocationService::class)->create($this->validDetails());

        $this->get(route('hr-admin.office-locations.index'))->assertRedirect(route('login'));
        $this->post(route('hr-admin.office-locations.store'), $this->validDetails())->assertRedirect(route('login'));
        $this->actingAs($this->user(User::ROLE_EMPLOYEE))
            ->get(route('hr-admin.office-locations.index'))->assertForbidden();
        $this->actingAs($this->user(User::ROLE_SUPERVISOR))
            ->patch(route('hr-admin.office-locations.activate', $location->id))->assertForbidden();
    }

    public function test_hr_admin_can_create_search_view_and_edit_location_without_changing_status(): void
    {
        $this->actingAs($this->user(User::ROLE_HR_ADMIN));

        $this->post(route('hr-admin.office-locations.store'), $this->validDetails())
            ->assertRedirect();
        $location = OfficeLocation::query()->firstOrFail();
        $this->assertFalse($location->is_active);
        $this->get(route('hr-admin.office-locations.index', ['search' => 'Development']))
            ->assertOk()->assertSee($location->name);
        $this->get(route('hr-admin.office-locations.show', $location->id))
            ->assertOk()->assertSee('GPS validation does not prove');

        app(OfficeLocationService::class)->activate($location);
        $this->put(route('hr-admin.office-locations.update', $location->id), array_merge(
            $this->validDetails(), ['name' => 'Updated boundary', 'is_active' => false],
        ))->assertRedirect();

        $this->assertTrue($location->fresh()->is_active);
        $this->assertSame('Updated boundary', $location->fresh()->name);
    }

    public function test_invalid_location_details_are_rejected_by_the_form(): void
    {
        $this->actingAs($this->user(User::ROLE_HR_ADMIN));

        $this->post(route('hr-admin.office-locations.store'), array_merge($this->validDetails(), [
            'latitude' => '91',
            'radius_meters' => 0,
        ]))->assertSessionHasErrors(['latitude', 'radius_meters']);

        $this->assertDatabaseCount('office_locations', 0);
    }

    public function test_activation_conflict_is_reported_without_deactivating_the_existing_location(): void
    {
        $this->actingAs($this->user(User::ROLE_HR_ADMIN));
        $service = app(OfficeLocationService::class);
        $active = $service->activate($service->create($this->validDetails()));
        $other = $service->create(array_merge($this->validDetails(), ['name' => 'Second boundary']));

        $this->patch(route('hr-admin.office-locations.activate', $other->id))
            ->assertSessionHasErrors('environment');

        $this->assertTrue($active->fresh()->is_active);
        $this->assertFalse($other->fresh()->is_active);
    }

    public function test_hr_admin_can_deactivate_without_deleting_location(): void
    {
        $this->actingAs($this->user(User::ROLE_HR_ADMIN));
        $service = app(OfficeLocationService::class);
        $location = $service->activate($service->create($this->validDetails()));

        $this->patch(route('hr-admin.office-locations.deactivate', $location->id))->assertRedirect();

        $this->assertDatabaseHas('office_locations', ['id' => $location->id, 'is_active' => false]);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    /** @return array{name: string, environment: string, latitude: string, longitude: string, radius_meters: int} */
    private function validDetails(): array
    {
        return [
            'name' => 'Temporary Test Location',
            'environment' => 'development',
            'latitude' => '19.0000000',
            'longitude' => '72.0000000',
            'radius_meters' => 100,
        ];
    }
}
