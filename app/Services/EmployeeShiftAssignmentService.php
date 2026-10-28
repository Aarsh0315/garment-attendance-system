<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EmployeeShiftAssignmentService
{
    public function assign(
        Employee $employee,
        Shift $shift,
        string $effectiveFrom,
        ?string $effectiveTo,
        User $assignedBy,
    ): EmployeeShiftAssignment {
        return DB::transaction(function () use ($employee, $shift, $effectiveFrom, $effectiveTo, $assignedBy): EmployeeShiftAssignment {
            $lockedEmployee = Employee::query()
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $employeeUser = $lockedEmployee->user()->lockForUpdate()->firstOrFail();
            $lockedShift = Shift::query()
                ->whereKey($shift->getKey())
                ->lockForUpdate()
                ->first();

            if (! $employeeUser->is_active) {
                $this->reject('employee_id', 'Select an active employee.');
            }

            if ($lockedShift === null || ! $lockedShift->is_active) {
                $this->reject('shift_id', 'Select an active shift.');
            }

            $startDate = $this->normalizeDate($effectiveFrom, 'effective_from');
            $endDate = $effectiveTo === null
                ? null
                : $this->normalizeDate($effectiveTo, 'effective_to');

            if ($endDate !== null && $endDate < $startDate) {
                $this->reject('effective_to', 'The assignment end date cannot be earlier than its start date.');
            }

            $overlaps = EmployeeShiftAssignment::query()
                ->where('employee_id', $lockedEmployee->getKey())
                ->when($endDate !== null, fn ($query) => $query->whereDate('effective_from', '<=', $endDate))
                ->where(function ($query) use ($startDate): void {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $startDate);
                })
                ->exists();

            if ($overlaps) {
                $this->reject('effective_from', 'This date range overlaps an existing shift assignment for the employee.');
            }

            $assignment = new EmployeeShiftAssignment;
            $assignment->employee_id = $lockedEmployee->getKey();
            $assignment->shift_id = $lockedShift->getKey();
            $assignment->effective_from = $startDate;
            $assignment->effective_to = $endDate;
            $assignment->assigned_by_user_id = $assignedBy->getKey();
            $assignment->save();

            return $assignment;
        });
    }

    /**
     * Return the one assignment covering a local work date, or reject attendance
     * processing when the employee has no valid assignment for that date.
     */
    public function forWorkDate(Employee $employee, string $workDate): EmployeeShiftAssignment
    {
        $date = $this->normalizeDate($workDate, 'work_date');

        $assignments = EmployeeShiftAssignment::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('id')
            ->limit(2)
            ->get();

        if ($assignments->isEmpty()) {
            $this->reject('work_date', 'No shift assignment covers this work date.');
        }

        if ($assignments->count() > 1) {
            $this->reject('work_date', 'More than one shift assignment covers this work date. Resolve the assignment history before continuing.');
        }

        return $assignments->firstOrFail();
    }

    private function normalizeDate(string $value, string $field): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
        ) {
            $this->reject($field, 'Enter a valid date in YYYY-MM-DD format.');
        }

        return $date->format('Y-m-d');
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
