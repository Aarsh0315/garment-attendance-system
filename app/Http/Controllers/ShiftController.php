<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftRequest;
use App\Http\Requests\ShiftStatusRequest;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($validated['search'] ?? '');
        $shifts = Shift::query()
            ->withCount('employeeAssignments')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('shifts.index', compact('shifts', 'search'));
    }

    public function create(): View
    {
        return view('shifts.create');
    }

    public function store(ShiftRequest $request): RedirectResponse
    {
        $shift = Shift::create($request->safe()->only([
            'name', 'start_time', 'end_time', 'ends_next_day', 'late_grace_minutes',
        ]) + ['is_active' => true]);

        return redirect()->route('hr-admin.shifts.show', $shift)->with('status', 'Shift created successfully.');
    }

    public function show(Shift $shift): View
    {
        $shift->loadCount('employeeAssignments');

        return view('shifts.show', compact('shift'));
    }

    public function edit(Shift $shift): View
    {
        $shift->loadCount('employeeAssignments');

        return view('shifts.edit', compact('shift'));
    }

    public function update(ShiftRequest $request, Shift $shift): RedirectResponse
    {
        DB::transaction(function () use ($request, $shift): void {
            $lockedShift = Shift::query()->whereKey($shift->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedShift->employeeAssignments()->exists() && ! $request->boolean('confirm_assignment_impact')) {
                throw ValidationException::withMessages([
                    'confirm_assignment_impact' => 'Confirm the effect on future schedules before saving changes to an assigned shift.',
                ]);
            }

            $lockedShift->fill($request->safe()->only([
                'name', 'start_time', 'end_time', 'ends_next_day', 'late_grace_minutes',
            ]));
            $lockedShift->save();
        });

        return redirect()->route('hr-admin.shifts.show', $shift)->with('status', 'Shift updated successfully.');
    }

    public function deactivate(ShiftStatusRequest $request, Shift $shift): RedirectResponse
    {
        return $this->setActive($shift, false);
    }

    public function activate(ShiftStatusRequest $request, Shift $shift): RedirectResponse
    {
        return $this->setActive($shift, true);
    }

    private function setActive(Shift $shift, bool $active): RedirectResponse
    {
        $changed = DB::transaction(function () use ($shift, $active): bool {
            $lockedShift = Shift::query()->whereKey($shift->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedShift->is_active === $active) {
                return false;
            }

            $lockedShift->is_active = $active;
            $lockedShift->save();

            return true;
        });

        $action = $active ? 'activated' : 'deactivated';

        return redirect()->route('hr-admin.shifts.show', $shift)->with(
            $changed ? 'status' : 'error',
            $changed
                ? "Shift {$action}. Existing assignment history was left unchanged."
                : 'The shift already has the requested status.',
        );
    }
}
