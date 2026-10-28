<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeAttendancePunchRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if (config('app.env') === 'local') {
            session()->flash('attendance_diagnostics', $this->diagnostics(
                'server_rejected_request_validation',
                $validator->errors()->keys(),
            ));
        }

        parent::failedValidation($validator);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->role === User::ROLE_EMPLOYEE
            && $user->is_active
            && ! $user->must_change_password;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy_meters' => ['required', 'numeric'],
        ];
    }

    /** @param array<int, string> $failedFields
     * @return array<string, mixed>
     */
    public function diagnostics(string $category, array $failedFields): array
    {
        $latitude = $this->number($this->input('latitude'));
        $longitude = $this->number($this->input('longitude'));
        $accuracy = $this->number($this->input('accuracy_meters'));

        return [
            'category' => $category,
            'browser_error_code' => null,
            'reported_accuracy_meters' => $accuracy === null ? null : round($accuracy, 1),
            'latitude_valid' => $latitude !== null && $latitude >= -90 && $latitude <= 90,
            'longitude_valid' => $longitude !== null && $longitude >= -180 && $longitude <= 180,
            'accuracy_valid' => $accuracy !== null && $accuracy > 0 && $accuracy <= 100,
            'server_rejection' => $category,
            'server_validation_fields' => array_values(array_intersect(
                $failedFields,
                ['latitude', 'longitude', 'accuracy_meters', 'location', 'environment', 'work_date', 'attendance', 'employee'],
            )),
        ];
    }

    private function number(mixed $value): ?float
    {
        if ((! is_int($value) && ! is_float($value) && ! is_string($value)) || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}
