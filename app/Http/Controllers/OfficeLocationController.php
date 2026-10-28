<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfficeLocationStatusRequest;
use App\Http\Requests\StoreOfficeLocationRequest;
use App\Http\Requests\UpdateOfficeLocationRequest;
use App\Services\OfficeLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeLocationController extends Controller
{
    public function __construct(private readonly OfficeLocationService $locations) {}

    public function index(Request $request): View
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($validated['search'] ?? '');
        $locations = $this->locations->paginate($search);

        return view('office-locations.index', compact('locations', 'search'));
    }

    public function create(): View
    {
        return view('office-locations.create');
    }

    public function store(StoreOfficeLocationRequest $request): RedirectResponse
    {
        $location = $this->locations->create($request->safe()->only([
            'name', 'environment', 'latitude', 'longitude', 'radius_meters',
        ]));

        return redirect()->route('hr-admin.office-locations.show', $location)
            ->with('status', 'Office location created inactive. Review it and activate it when appropriate.');
    }

    public function show(int $location): View
    {
        $location = $this->locations->findOrFail($location);

        return view('office-locations.show', compact('location'));
    }

    public function edit(int $location): View
    {
        $location = $this->locations->findOrFail($location);

        return view('office-locations.edit', compact('location'));
    }

    public function update(UpdateOfficeLocationRequest $request, int $location): RedirectResponse
    {
        $location = $this->locations->update(
            $this->locations->findOrFail($location),
            $request->safe()->only(['name', 'environment', 'latitude', 'longitude', 'radius_meters']),
        );

        return redirect()->route('hr-admin.office-locations.show', $location)
            ->with('status', 'Office location details updated.');
    }

    public function activate(OfficeLocationStatusRequest $request, int $location): RedirectResponse
    {
        $this->locations->activate($this->locations->findOrFail($location));

        return redirect()->route('hr-admin.office-locations.show', $location)
            ->with('status', 'Office location activated for future attendance validation.');
    }

    public function deactivate(OfficeLocationStatusRequest $request, int $location): RedirectResponse
    {
        $this->locations->deactivate($this->locations->findOrFail($location));

        return redirect()->route('hr-admin.office-locations.show', $location)
            ->with('status', 'Office location deactivated. Existing attendance history is retained.');
    }
}
