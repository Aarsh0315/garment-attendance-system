<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends EmployeeProfileRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->profileRules() + [
            'password' => ['bail', 'required', 'string', Password::min(12), 'confirmed'],
        ];
    }
}
