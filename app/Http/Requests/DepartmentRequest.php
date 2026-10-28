<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()?->role === User::ROLE_HR_ADMIN;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $normalizedName = mb_strtolower(trim($value), 'UTF-8');
                    $query = Department::query()->where('normalized_name', $normalizedName);
                    $department = $this->route('department');

                    if ($department instanceof Department) {
                        $query->whereKeyNot($department->getKey());
                    }

                    if ($query->exists()) {
                        $fail('A department with this name already exists.');
                    }
                },
            ],
        ];
    }
}
