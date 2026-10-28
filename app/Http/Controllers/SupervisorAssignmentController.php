<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupervisorAssignmentRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\SupervisorAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupervisorAssignmentController extends Controller
{
    public function index(Employee $employee): View
    {
        $employee->load([
            'user',
            'supervisorAssignments.supervisor',
        ]);

        return view('supervisor-assignments.index', compact('employee'));
    }

    public function create(Employee $employee): View|RedirectResponse
    {
        $employee->load(['user', 'supervisorAssignments']);

        if (! $employee->user->is_active) {
            return redirect()
                ->route('hr-admin.employees.supervisor-assignments.index', $employee)
                ->with('error', 'Supervisor assignments cannot be changed for an inactive employee.');
        }

        $supervisors = User::query()
            ->where('role', User::ROLE_SUPERVISOR)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $currentAssignment = $employee->supervisorAssignments
            ->first(fn ($assignment): bool => $assignment->effective_to === null);
        $lastAssignment = $employee->supervisorAssignments->last();
        $nextStartDate = $lastAssignment?->effective_to?->copy()->addDay()->toDateString();

        return view('supervisor-assignments.create', compact(
            'employee',
            'supervisors',
            'currentAssignment',
            'nextStartDate',
        ));
    }

    public function store(
        StoreSupervisorAssignmentRequest $request,
        Employee $employee,
        SupervisorAssignmentService $assignmentService,
    ): RedirectResponse {
        $data = $request->validated();
        $assignmentService->assign($employee, (int) $data['supervisor_user_id'], $data['effective_from']);

        return redirect()
            ->route('hr-admin.employees.supervisor-assignments.index', $employee)
            ->with('status', 'Supervisor assignment saved. Previous assignment history was retained.');
    }
}
