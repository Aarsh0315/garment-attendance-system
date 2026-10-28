<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

abstract class OfficeLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->role === User::ROLE_HR_ADMIN
            && $user->is_active
            && ! $user->must_change_password;
    }

    /** @return array<string, array<int, mixed>> */
    protected function detailRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'string', 'max:16'],
            'latitude' => ['required', 'numeric', 'decimal:0,7', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'decimal:0,7', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ];
    }
}
