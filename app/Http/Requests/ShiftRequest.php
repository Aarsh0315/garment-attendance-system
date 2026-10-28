<?php

namespace App\Http\Requests;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ShiftRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['ends_next_day' => $this->boolean('ends_next_day')]);
    }

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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'ends_next_day' => ['required', 'boolean'],
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:65535'],
        ];

        $shift = $this->route('shift');

        if ($shift instanceof Shift && $shift->employeeAssignments()->exists()) {
            $rules['confirm_assignment_impact'] = ['accepted'];
        }

        return $rules;
    }
}
