<?php

namespace App\Http\Requests;

use App\Models\Employee;

class UpdateEmployeeRequest extends EmployeeProfileRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return $this->profileRules($employee instanceof Employee ? $employee : null);
    }
}
