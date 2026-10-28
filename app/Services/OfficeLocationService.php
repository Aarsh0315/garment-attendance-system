<?php

namespace App\Services;

use App\Models\OfficeLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class OfficeLocationService
{
    private const MAX_RADIUS_METERS = 4294967295;

    /** @return Collection<int, OfficeLocation> */
    public function all(): Collection
    {
        return OfficeLocation::query()->orderBy('id')->get();
    }

    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return OfficeLocation::query()
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('environment', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function findOrFail(int $id): OfficeLocation
    {
        return OfficeLocation::query()->findOrFail($id);
    }

    /** New locations start inactive and must be explicitly activated. */
    public function create(array $attributes): OfficeLocation
    {
        $details = $this->validateDetails($attributes, partial: false);

        return DB::transaction(function () use ($details): OfficeLocation {
            $location = new OfficeLocation;
            $location->fill($details);
            $location->is_active = false;
            $location->save();

            return $location;
        });
    }

    /** Update descriptive and boundary details without changing activation status. */
    public function update(OfficeLocation $location, array $attributes): OfficeLocation
    {
        $details = $this->validateDetails($attributes, partial: true);

        return $this->safelyPersist(function () use ($location, $details): OfficeLocation {
            $lockedLocation = OfficeLocation::query()
                ->whereKey($location->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $newEnvironment = $details['environment'] ?? $lockedLocation->environment;

            if ($lockedLocation->is_active) {
                $this->ensureNoOtherActiveLocation($newEnvironment, $lockedLocation->getKey());
            }

            $lockedLocation->fill($details);
            $lockedLocation->save();

            return $lockedLocation;
        });
    }

    public function activate(OfficeLocation $location): OfficeLocation
    {
        return $this->safelyPersist(function () use ($location): OfficeLocation {
            $lockedLocation = OfficeLocation::query()
                ->whereKey($location->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedLocation->is_active) {
                $this->ensureNoOtherActiveLocation($lockedLocation->environment, $lockedLocation->getKey());
                $lockedLocation->is_active = true;
                $lockedLocation->save();
            }

            return $lockedLocation;
        });
    }

    public function deactivate(OfficeLocation $location): OfficeLocation
    {
        return DB::transaction(function () use ($location): OfficeLocation {
            $lockedLocation = OfficeLocation::query()
                ->whereKey($location->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLocation->is_active) {
                $lockedLocation->is_active = false;
                $lockedLocation->save();
            }

            return $lockedLocation;
        });
    }

    /** @return array<string, mixed> */
    private function validateDetails(array $attributes, bool $partial): array
    {
        $allowed = ['name', 'environment', 'latitude', 'longitude', 'radius_meters'];
        $unsupported = array_diff(array_keys($attributes), $allowed);

        if ($unsupported !== []) {
            throw ValidationException::withMessages([
                'attributes' => 'Only location details can be changed here. Use the activation actions to change active status.',
            ]);
        }

        if (isset($attributes['environment']) && is_string($attributes['environment'])) {
            $attributes['environment'] = mb_strtolower(trim($attributes['environment']), 'UTF-8');
        }

        $presence = $partial ? 'sometimes|required|' : 'required|';
        $rules = [
            'name' => $presence.'string|max:255',
            'environment' => $presence.'string|max:16',
            'latitude' => [$partial ? 'sometimes' : 'required', 'numeric', 'decimal:0,7', 'between:-90,90'],
            'longitude' => [$partial ? 'sometimes' : 'required', 'numeric', 'decimal:0,7', 'between:-180,180'],
            'radius_meters' => [$partial ? 'sometimes' : 'required', 'integer', 'min:1', 'max:'.self::MAX_RADIUS_METERS],
        ];

        return Validator::make($attributes, $rules)->validate();
    }

    private function ensureNoOtherActiveLocation(string $environment, int $exceptId): void
    {
        $exists = OfficeLocation::query()
            ->whereRaw('LOWER(environment) = ?', [$environment])
            ->where('is_active', true)
            ->where('id', '!=', $exceptId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'environment' => 'Another active location already exists for this environment.',
            ]);
        }
    }

    private function safelyPersist(callable $operation): OfficeLocation
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            if (! $this->isActiveEnvironmentConflict($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'environment' => 'Another active location already exists for this environment.',
            ]);
        }
    }

    private function isActiveEnvironmentConflict(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage(), 'UTF-8');

        return str_contains($message, 'office_active_environment_unique')
            || str_contains($message, 'office_locations.active_environment');
    }
}
