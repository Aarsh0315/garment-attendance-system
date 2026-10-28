<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupervisorAssignmentService
{
    public function assign(Employee $employee, int $supervisorUserId, string $effectiveFrom): EmployeeSupervisorAssignment
    {
        return DB::transaction(function () use ($employee, $supervisorUserId, $effectiveFrom): EmployeeSupervisorAssignment {
            $lockedEmployee = Employee::query()
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $employeeUser = $lockedEmployee->user()->lockForUpdate()->firstOrFail();

            if (! $employeeUser->is_active) {
                $this->reject('effective_from', 'A supervisor cannot be assigned to an inactive employee.');
            }

            $supervisor = User::query()
                ->whereKey($supervisorUserId)
                ->lockForUpdate()
                ->first();

            if ($supervisor === null || $supervisor->role !== User::ROLE_SUPERVISOR || ! $supervisor->is_active) {
                $this->reject('supervisor_user_id', 'Select an active account with the Supervisor role.');
            }

            if (! Carbon::hasFormat($effectiveFrom, 'Y-m-d')) {
                $this->reject('effective_from', 'Enter a valid assignment start date.');
            }

            $startDate = Carbon::createFromFormat('!Y-m-d', $effectiveFrom);

            if ($effectiveFrom < $lockedEmployee->joining_date->toDateString()) {
                $this->reject('effective_from', 'The assignment start date cannot be before the employee joining date.');
            }

            $assignments = $this->lockAssignments($lockedEmployee);
            $this->validateAssignmentHistory($assignments, 'effective_from');
            $current = $assignments->first(fn (EmployeeSupervisorAssignment $assignment): bool => $assignment->effective_to === null);

            if ($current !== null) {
                if ($effectiveFrom <= $current->effective_from->toDateString()) {
                    $this->reject('effective_from', 'The replacement must start after the current assignment begins.');
                }

                $current->effective_to = $startDate->copy()->subDay()->toDateString();
                $current->save();
            } elseif ($assignments->isNotEmpty()) {
                /** @var EmployeeSupervisorAssignment $lastAssignment */
                $lastAssignment = $assignments->last();
                $nextStartDate = $lastAssignment->effective_to?->copy()->addDay()->toDateString();

                if ($nextStartDate === null || $effectiveFrom !== $nextStartDate) {
                    $this->reject('effective_from', 'The next assignment must start the day after the previous assignment ends.');
                }
            }

            $assignment = new EmployeeSupervisorAssignment;
            $assignment->employee_id = $lockedEmployee->getKey();
            $assignment->supervisor_user_id = $supervisor->getKey();
            $assignment->effective_from = $effectiveFrom;
            $assignment->save();

            return $assignment;
        });
    }

    public function deactivateEmployee(Employee $employee, string $effectiveDate): void
    {
        DB::transaction(function () use ($employee, $effectiveDate): void {
            $lockedEmployee = Employee::query()
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user = $lockedEmployee->user()->lockForUpdate()->firstOrFail();

            if (! $user->is_active) {
                $this->reject('effective_date', 'This employee account is already inactive.');
            }

            if (! Carbon::hasFormat($effectiveDate, 'Y-m-d')) {
                $this->reject('effective_date', 'Enter a valid deactivation date.');
            }

            if ($effectiveDate < $lockedEmployee->joining_date->toDateString()) {
                $this->reject('effective_date', 'The deactivation date cannot be before the employee joining date.');
            }

            $assignments = $this->lockAssignments($lockedEmployee);
            $this->validateAssignmentHistory($assignments, 'effective_date', $effectiveDate);

            $currentAssignment = $assignments->first(fn (EmployeeSupervisorAssignment $assignment): bool => $assignment->effective_to === null);

            if ($currentAssignment !== null) {
                $currentAssignment->effective_to = $effectiveDate;
                $currentAssignment->save();
            }

            $user->is_active = false;
            $user->save();
        });
    }

    /** @return Collection<int, EmployeeSupervisorAssignment> */
    private function lockAssignments(Employee $employee): Collection
    {
        return EmployeeSupervisorAssignment::query()
            ->where('employee_id', $employee->getKey())
            ->orderBy('effective_from')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, EmployeeSupervisorAssignment>  $assignments
     */
    private function validateAssignmentHistory(
        Collection $assignments,
        string $errorField,
        ?string $effectiveDate = null,
    ): void {
        $previousEnd = null;
        $hasPrevious = false;

        foreach ($assignments as $assignment) {
            $startsOn = $assignment->effective_from->toDateString();
            $endsOn = $assignment->effective_to?->toDateString();

            if ($endsOn !== null && $endsOn < $startsOn) {
                $this->reject($errorField, 'The supervisor assignment history contains an invalid date range. Resolve it before continuing.');
            }

            if ($hasPrevious && ($previousEnd === null || $startsOn <= $previousEnd)) {
                $this->reject($errorField, 'The supervisor assignment history contains overlapping date ranges. Resolve them before continuing.');
            }

            if ($effectiveDate !== null && $startsOn > $effectiveDate) {
                $this->reject($errorField, 'The selected date cannot be before a scheduled supervisor assignment.');
            }

            if ($effectiveDate !== null && $endsOn !== null && $endsOn > $effectiveDate) {
                $this->reject($errorField, 'The selected date cannot precede the end of an existing supervisor assignment.');
            }

            $previousEnd = $endsOn;
            $hasPrevious = true;
        }
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
