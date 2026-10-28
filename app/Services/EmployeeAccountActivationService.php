<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeAccountActivationService
{
    public function activate(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $lockedEmployee = Employee::query()
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $user = $lockedEmployee->user()->lockForUpdate()->firstOrFail();

            if ($user->is_active) {
                throw ValidationException::withMessages([
                    'employee' => 'This employee account is already active.',
                ]);
            }

            $user->is_active = true;
            $user->save();
        });
    }
}
