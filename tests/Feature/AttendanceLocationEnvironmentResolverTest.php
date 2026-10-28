<?php

namespace Tests\Feature;

use App\Models\OfficeLocation;
use App\Services\Attendance\AttendanceLocationEnvironmentResolver;
use App\Services\Attendance\AttendanceLocationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class AttendanceLocationEnvironmentResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_exact_environment_from_laravel_configuration(): void
    {
        Config::set('attendance.location_environment', 'development');

        $this->assertSame('development', app(AttendanceLocationEnvironmentResolver::class)->resolve());
    }

    public function test_missing_blank_whitespace_and_overlength_values_fail_closed(): void
    {
        foreach ([null, '', '   ', str_repeat('x', 17)] as $environment) {
            Config::set('attendance.location_environment', $environment);

            try {
                app(AttendanceLocationEnvironmentResolver::class)->resolve();
                $this->fail('An invalid environment configuration must be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_surrounding_whitespace_is_rejected_instead_of_silently_trimmed(): void
    {
        Config::set('attendance.location_environment', ' development ');

        $this->expectException(InvalidArgumentException::class);
        app(AttendanceLocationEnvironmentResolver::class)->resolve();
    }

    public function test_configuration_override_is_read_through_laravels_config_repository(): void
    {
        Config::set('attendance.location_environment', 'testing');

        $this->assertSame('testing', app(AttendanceLocationEnvironmentResolver::class)->resolve());
    }

    public function test_location_lookup_does_not_fall_back_to_another_environment(): void
    {
        $this->createLocation('development');
        Config::set('attendance.location_environment', 'production');
        $environment = app(AttendanceLocationEnvironmentResolver::class)->resolve();

        try {
            app(AttendanceLocationValidator::class)->validate(0, 0, 10, $environment);
            $this->fail('A missing production location must not select the development location.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }
    }

    public function test_case_mismatch_does_not_select_a_location_under_a_case_insensitive_database_collation(): void
    {
        $this->createLocation('development');
        Config::set('attendance.location_environment', 'Development');
        $environment = app(AttendanceLocationEnvironmentResolver::class)->resolve();

        try {
            app(AttendanceLocationValidator::class)->validate(0, 0, 10, $environment);
            $this->fail('Environment lookup must use an exact case-sensitive match.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('environment', $exception->errors());
        }
    }

    private function createLocation(string $environment): OfficeLocation
    {
        return OfficeLocation::create([
            'name' => 'Test Location',
            'environment' => $environment,
            'latitude' => '0.0000000',
            'longitude' => '0.0000000',
            'radius_meters' => 100,
            'is_active' => true,
        ]);
    }
}
