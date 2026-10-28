<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeactivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()?->role === User::ROLE_HR_ADMIN;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'effective_date' => ['bail', 'required', 'date_format:Y-m-d'],
        ];
    }
}
