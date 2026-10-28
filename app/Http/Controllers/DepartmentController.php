<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateDepartmentRequest;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $departments = Department::query()
            ->withCount('employees')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('departments.index', compact('departments', 'search'));
    }

    public function create(): View
    {
        return view('departments.create');
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        try {
            $department = Department::create($request->validated());
        } catch (QueryException $exception) {
            $this->rethrowDepartmentNameConflict($exception);
        }

        return redirect()
            ->route('hr-admin.departments.show', $department)
            ->with('status', 'Department created successfully.');
    }

    public function show(Department $department): View
    {
        $department->loadCount('employees');

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        try {
            $department->update($request->validated());
        } catch (QueryException $exception) {
            $this->rethrowDepartmentNameConflict($exception);
        }

        return redirect()
            ->route('hr-admin.departments.show', $department)
            ->with('status', 'Department updated successfully.');
    }

    public function deactivate(Department $department): RedirectResponse
    {
        if (! $department->is_active) {
            return redirect()
                ->route('hr-admin.departments.show', $department)
                ->with('error', 'This department is already inactive.');
        }

        $department->is_active = false;
        $department->save();

        return redirect()
            ->route('hr-admin.departments.show', $department)
            ->with('status', 'Department deactivated. Existing employee records remain linked.');
    }

    public function activate(ActivateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $activated = DB::transaction(function () use ($department): bool {
            $lockedDepartment = Department::query()
                ->whereKey($department->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedDepartment->is_active) {
                return false;
            }

            $lockedDepartment->is_active = true;
            $lockedDepartment->save();

            return true;
        });

        return redirect()
            ->route('hr-admin.departments.show', $department)
            ->with($activated ? 'status' : 'error', $activated
                ? 'Department activated. Existing employee and historical records remain unchanged.'
                : 'This department is already active.');
    }

    private function rethrowDepartmentNameConflict(QueryException $exception): never
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $databaseMessage = strtolower($exception->getMessage());
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $isUniqueViolation = in_array($sqlState, ['23000', '19'], true)
            || $driverCode === '19';
        $isDepartmentNameConflict = $isUniqueViolation
            && (str_contains($databaseMessage, 'normalized_name')
                || str_contains($databaseMessage, 'departments_normalized_name_unique'));

        if (! $isDepartmentNameConflict) {
            throw $exception;
        }

        throw ValidationException::withMessages([
            'name' => 'A department with this name already exists.',
        ]);
    }
}
