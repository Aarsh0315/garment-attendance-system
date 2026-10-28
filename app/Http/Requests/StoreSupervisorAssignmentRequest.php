<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupervisorAssignmentRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'supervisor_user_id' => ['required', 'integer', 'exists:users,id'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
