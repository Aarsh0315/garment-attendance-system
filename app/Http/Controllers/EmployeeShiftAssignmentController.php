<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeShiftAssignmentRequest;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Services\EmployeeShiftAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeShiftAssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = EmployeeShiftAssignment::query()
            ->with(['employee.user', 'shift', 'assignedBy'])
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(20);

        return view('shift-assignments.index', compact('assignments'));
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->orderBy('employee_code')
            ->get();
        $shifts = Shift::query()->where('is_active', true)->orderBy('name')->get();

        return view('shift-assignments.create', compact('employees', 'shifts'));
    }

    public function store(
        StoreEmployeeShiftAssignmentRequest $request,
        EmployeeShiftAssignmentService $assignmentService,
    ): RedirectResponse {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id']);
        $shift = Shift::query()->findOrFail($data['shift_id']);

        $assignmentService->assign(
            $employee,
            $shift,
            $data['effective_from'],
            $data['effective_to'] ?? null,
            $request->user(),
        );

        return redirect()->route('hr-admin.shift-assignments.index')
            ->with('status', 'Shift assignment saved. Existing assignment history was retained.');
    }
}
