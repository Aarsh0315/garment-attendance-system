<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateEmployeeRequest;
use App\Http\Requests\DeactivateEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeAccountActivationService;
use App\Services\SupervisorAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $employees = Employee::query()
            ->with(['user', 'department'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $like = '%'.$search.'%';

                    $query->where('employee_code', 'like', $like)
                        ->orWhere('designation', 'like', $like)
                        ->orWhereHas('user', function ($query) use ($like): void {
                            $query->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        })
                        ->orWhereHas('department', function ($query) use ($like): void {
                            $query->where('name', 'like', $like);
                        });
                });
            })
            ->orderBy('employee_code')
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search'));
    }

    public function create(): View
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('employees.create', compact('departments'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $employee = DB::transaction(function () use ($data): Employee {
            $this->lockActiveDepartment((int) $data['department_id']);

            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role = User::ROLE_EMPLOYEE;
            $user->is_active = true;
            $user->must_change_password = true;
            $user->save();

            return $user->employee()->create([
                'employee_code' => $data['employee_code'],
                'department_id' => $data['department_id'],
                'designation' => $data['designation'],
                'joining_date' => $data['joining_date'],
                'phone' => $data['phone'] ?? null,
            ]);
        });

        return redirect()
            ->route('hr-admin.employees.show', $employee)
            ->with('status', 'Employee account and profile created successfully.');
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'user',
            'department',
            'supervisorAssignments.supervisor',
            'shiftAssignments.shift',
        ]);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['user', 'department']);
        $departments = Department::query()
            ->where('is_active', true)
            ->orWhere('id', $employee->department_id)
            ->orderBy('name')
            ->get();

        return view('employees.edit', compact('employee', 'departments'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $employee): void {
            $employee = Employee::query()->whereKey($employee->getKey())->lockForUpdate()->firstOrFail();

            if ((int) $data['department_id'] !== (int) $employee->department_id) {
                $this->lockActiveDepartment((int) $data['department_id']);
            }

            $user = $employee->user()->lockForUpdate()->firstOrFail();

            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->save();

            $employee->employee_code = $data['employee_code'];
            $employee->department_id = $data['department_id'];
            $employee->designation = $data['designation'];
            $employee->joining_date = $data['joining_date'];
            $employee->phone = $data['phone'] ?? null;
            $employee->save();
        });

        return redirect()
            ->route('hr-admin.employees.show', $employee)
            ->with('status', 'Employee details updated successfully.');
    }

    public function deactivate(
        DeactivateEmployeeRequest $request,
        Employee $employee,
        SupervisorAssignmentService $assignmentService,
    ): RedirectResponse {
        $assignmentService->deactivateEmployee($employee, $request->validated('effective_date'));

        return redirect()
            ->route('hr-admin.employees.show', $employee)
            ->with('status', 'Employee deactivated. The profile and history were retained.');
    }

    public function activate(
        ActivateEmployeeRequest $request,
        Employee $employee,
        EmployeeAccountActivationService $activationService,
    ): RedirectResponse {
        $activationService->activate($employee);

        return redirect()
            ->route('hr-admin.employees.show', $employee)
            ->with('status', 'Employee account activated. Existing profile and history were retained.');
    }

    private function lockActiveDepartment(int $departmentId): Department
    {
        $department = Department::query()
            ->whereKey($departmentId)
            ->lockForUpdate()
            ->first();

        if ($department === null || ! $department->is_active) {
            throw ValidationException::withMessages([
                'department_id' => 'Select an active department.',
            ]);
        }

        return $department;
    }
}
