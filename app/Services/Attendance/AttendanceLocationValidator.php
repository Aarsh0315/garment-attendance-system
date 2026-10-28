<?php

namespace App\Services\Attendance;

use App\Models\OfficeLocation;
use Illuminate\Validation\ValidationException;

final class AttendanceLocationValidator
{
    /** Mean Earth radius used by the Haversine calculation, in metres. */
    public const EARTH_RADIUS_METERS = 6_371_008.8;

    private const MAX_ACCURACY_METERS = 100.0;

    /**
     * Validate a reported position against the active location for an environment.
     *
     * @return array{office_location: OfficeLocation, latitude: float, longitude: float, accuracy_meters: float, distance_meters: float}
     */
    public function validate(
        mixed $latitude,
        mixed $longitude,
        mixed $accuracyMeters,
        mixed $environment,
    ): array {
        $latitude = $this->finiteNumber($latitude);
        if ($latitude === null || $latitude < -90 || $latitude > 90) {
            $this->reject('latitude', 'Enter a valid latitude between -90 and 90.');
        }

        $longitude = $this->finiteNumber($longitude);
        if ($longitude === null || $longitude < -180 || $longitude > 180) {
            $this->reject('longitude', 'Enter a valid longitude between -180 and 180.');
        }

        $accuracyMeters = $this->finiteNumber($accuracyMeters);
        if ($accuracyMeters === null || $accuracyMeters <= 0 || $accuracyMeters > self::MAX_ACCURACY_METERS) {
            $this->reject('accuracy_meters', 'Location accuracy must be greater than zero and at most 100 metres.');
        }

        $environment = $this->normalizeEnvironment($environment);
        $locations = OfficeLocation::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (OfficeLocation $location): bool => $location->environment === $environment);

        if ($locations->count() !== 1) {
            $this->reject('environment', 'No single active attendance location is configured for this environment.');
        }

        /** @var OfficeLocation $location */
        $location = $locations->firstOrFail();
        $distanceMeters = $this->distanceInMeters(
            $latitude,
            $longitude,
            (float) $location->latitude,
            (float) $location->longitude,
        );

        // Six decimal places preserve sub-millimetre precision and absorb float rounding at the boundary.
        $distanceMeters = round($distanceMeters, 6);
        if ($distanceMeters > $location->radius_meters) {
            $this->reject('location', 'The reported position is outside the permitted attendance area.');
        }

        return [
            'office_location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracyMeters,
            'distance_meters' => $distanceMeters,
        ];
    }

    private function finiteNumber(mixed $value): ?float
    {
        if ((! is_int($value) && ! is_float($value) && ! is_string($value)) || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }

    private function normalizeEnvironment(mixed $environment): string
    {
        if (! is_string($environment)) {
            $this->reject('environment', 'A valid environment is required.');
        }

        if (
            trim($environment) === ''
            || trim($environment) !== $environment
            || mb_strlen($environment, 'UTF-8') > 16
        ) {
            $this->reject('environment', 'A valid environment is required.');
        }

        return $environment;
    }

    private function distanceInMeters(float $latitude, float $longitude, float $officeLatitude, float $officeLongitude): float
    {
        $latitudeDelta = deg2rad($officeLatitude - $latitude);
        $longitudeDelta = deg2rad($officeLongitude - $longitude);
        $latitude = deg2rad($latitude);
        $officeLatitude = deg2rad($officeLatitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($latitude) * cos($officeLatitude) * sin($longitudeDelta / 2) ** 2;
        $haversine = min(1.0, max(0.0, $haversine));

        return 2 * self::EARTH_RADIUS_METERS * asin(sqrt($haversine));
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
