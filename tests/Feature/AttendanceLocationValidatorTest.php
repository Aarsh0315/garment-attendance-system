<?php

namespace Tests\Feature;

use App\Models\OfficeLocation;
use App\Services\Attendance\AttendanceLocationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceLocationValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_a_position_inside_the_active_location_radius(): void
    {
        $location = $this->activeLocation();

        $result = $this->validator()->validate(0.0001, 0, 20, 'development');

        $this->assertTrue($result['office_location']->is($location));
        $this->assertLessThan(100, $result['distance_meters']);
    }

    public function test_environment_matching_is_exact_and_does_not_normalize_case_or_whitespace(): void
    {
        $this->activeLocation();

        foreach (['Development', ' development', 'development '] as $environment) {
            try {
                $this->validator()->validate(0, 0, 10, $environment);
                $this->fail('Environment labels must match exactly and cannot include surrounding whitespace.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('environment', $exception->errors());
            }
        }
    }

    public function test_it_accepts_a_position_exactly_on_the_radius_boundary(): void
    {
        $location = $this->activeLocation();
        $latitude = rad2deg(100 / AttendanceLocationValidator::EARTH_RADIUS_METERS);

        $result = $this->validator()->validate($latitude, 0, 20, 'development');

        $this->assertTrue($result['office_location']->is($location));
        $this->assertSame(100.0, $result['distance_meters']);
    }

    public function test_it_rejects_a_position_outside_the_radius(): void
    {
        $this->activeLocation();

        $this->expectException(ValidationException::class);
        $this->validator()->validate(0.001, 0, 20, 'development');
    }

    public function test_identical_coordinates_have_zero_distance(): void
    {
        $location = $this->activeLocation();

        $result = $this->validator()->validate($location->latitude, $location->longitude, 1, 'development');

        $this->assertSame(0.0, $result['distance_meters']);
    }

    public function test_it_rejects_invalid_latitude_and_longitude_values(): void
    {
        $this->activeLocation();

        foreach ([null, 'north', -90.0001, 90.0001, INF, NAN] as $latitude) {
            try {
                $this->validator()->validate($latitude, 0, 10, 'development');
                $this->fail('Invalid latitude should be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('latitude', $exception->errors());
            }
        }

        foreach ([null, 'east', -180.0001, 180.0001, INF, NAN] as $longitude) {
            try {
                $this->validator()->validate(0, $longitude, 10, 'development');
                $this->fail('Invalid longitude should be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('longitude', $exception->errors());
            }
        }
    }

    public function test_it_rejects_missing_invalid_or_non_finite_accuracy(): void
    {
        $this->activeLocation();

        foreach ([null, 0, -1, 'unknown', INF, NAN] as $accuracy) {
            try {
                $this->validator()->validate(0, 0, $accuracy, 'development');
                $this->fail('Invalid GPS accuracy should be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('accuracy_meters', $exception->errors());
            }
        }
    }

    public function test_accuracy_of_exactly_100_metres_is_accepted_and_greater_accuracy_is_rejected(): void
    {
        $this->activeLocation();

        $this->assertSame(0.0, $this->validator()->validate(0, 0, 100, 'development')['distance_meters']);

        try {
            $this->validator()->validate(0, 0, 100.01, 'development');
            $this->fail('Accuracy greater than 100 metres should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('accuracy_meters', $exception->errors());
        }
    }

    public function test_no_active_location_is_rejected_and_inactive_locations_are_ignored(): void
    {
        $this->location(isActive: false);

        try {
            $this->validator()->validate(0, 0, 10, 'development');
            $this->fail('An inactive location must not be used.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }
    }

    public function test_it_resolves_the_active_location_for_the_requested_environment(): void
    {
        $development = $this->activeLocation();
        $testing = $this->location(environment: 'testing', isActive: true, latitude: '20.0000000');

        $result = $this->validator()->validate(20, 0, 10, 'testing');

        $this->assertTrue($result['office_location']->is($testing));
        $this->assertFalse($result['office_location']->is($development));
    }

    public function test_validation_does_not_create_attendance_records_or_events(): void
    {
        $this->activeLocation();

        $this->validator()->validate(0, 0, 10, 'development');

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertDatabaseCount('attendance_events', 0);
    }

    private function validator(): AttendanceLocationValidator
    {
        return app(AttendanceLocationValidator::class);
    }

    private function activeLocation(): OfficeLocation
    {
        return $this->location(isActive: true);
    }

    private function location(
        string $environment = 'development',
        bool $isActive = false,
        string $latitude = '0.0000000',
    ): OfficeLocation {
        return OfficeLocation::create([
            'name' => 'Test Location',
            'environment' => $environment,
            'latitude' => $latitude,
            'longitude' => '0.0000000',
            'radius_meters' => 100,
            'is_active' => $isActive,
        ]);
    }
}
