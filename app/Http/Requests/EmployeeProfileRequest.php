<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class EmployeeProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['name', 'email', 'employee_code'] as $field) {
            if (! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->input($field));
            $normalized[$field] = match ($field) {
                'email' => Str::lower($value),
                'employee_code' => mb_strtoupper($value, 'UTF-8'),
                default => $value,
            };
        }

        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()?->role === User::ROLE_HR_ADMIN;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function profileRules(?Employee $employee = null): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($employee): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $query = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($value)]);

                    if ($employee !== null) {
                        $query->whereKeyNot($employee->user_id);
                    }

                    if ($query->exists()) {
                        $fail('This email address is already in use.');
                    }
                },
            ],
            'employee_code' => [
                'bail',
                'required',
                'string',
                'max:64',
                Rule::unique('employees', 'employee_code')->ignore($employee?->getKey()),
            ],
            'department_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) use ($employee): void {
                    $isUnchangedDepartment = $employee !== null
                        && (int) $this->input('department_id') === (int) $employee->department_id;

                    if (! $isUnchangedDepartment) {
                        $query->where('is_active', true);
                    }
                }),
            ],
            'designation' => ['bail', 'required', 'string', 'max:255'],
            'joining_date' => ['bail', 'required', 'date_format:Y-m-d'],
            'phone' => ['bail', 'nullable', 'string', 'max:32'],
        ];
    }
}
