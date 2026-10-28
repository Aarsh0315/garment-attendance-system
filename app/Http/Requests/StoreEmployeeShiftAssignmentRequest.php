<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->role === User::ROLE_HR_ADMIN
            && $user->is_active
            && ! $user->must_change_password;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'bail', 'required', 'integer', Rule::exists('employees', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_numeric($value) && ! Employee::query()->whereKey($value)->whereHas('user', fn ($query) => $query->where('is_active', true))->exists()) {
                        $fail('Select an active employee.');
                    }
                },
            ],
            'shift_id' => [
                'bail', 'required', 'integer', Rule::exists('shifts', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_numeric($value) && ! Shift::query()->whereKey($value)->where('is_active', true)->exists()) {
                        $fail('Select an active shift.');
                    }
                },
            ],
            'effective_from' => ['bail', 'required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
        ];
    }
}
