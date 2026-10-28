<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeAttendancePunchRequest;
use App\Models\Employee;
use App\Services\Attendance\AttendancePunchService;
use App\Support\AttendanceClock;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class EmployeeAttendanceController extends Controller
{
    public function __construct(private readonly AttendancePunchService $punches) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $employee = Employee::query()->where('user_id', $user->getKey())->first();
        $instant = CarbonImmutable::now(AttendanceClock::STORAGE_TIMEZONE);
        $workDate = AttendanceClock::workDate($instant);
        $todayRecord = $employee?->attendanceRecords()
            ->whereDate('work_date', $workDate->toDateString())
            ->first();
        $openRecords = $employee === null
            ? collect()
            : $employee->attendanceRecords()
                ->whereNotNull('check_in_at')
                ->whereNull('check_out_at')
                ->orderByDesc('work_date')
                ->orderByDesc('id')
                ->limit(2)
                ->get();

        return view('employee-attendance.index', [
            'employee' => $employee,
            'workDate' => $workDate,
            'todayRecord' => $todayRecord,
            'openRecords' => $openRecords,
            'canCheckIn' => $employee !== null && $todayRecord === null && $openRecords->isEmpty(),
            'canCheckOut' => $employee !== null && $openRecords->count() === 1,
            'hasMultipleOpenRecords' => $openRecords->count() > 1,
            'late' => $todayRecord === null ? null : $this->punches->isLate($todayRecord),
        ]);
    }

    public function checkIn(EmployeeAttendancePunchRequest $request): RedirectResponse
    {
        try {
            $this->punches->checkIn(
                $request->user(),
                $request->validated('latitude'),
                $request->validated('longitude'),
                $request->validated('accuracy_meters'),
            );
        } catch (InvalidArgumentException) {
            $this->flashDiagnostics($request, 'server_rejected_configuration', ['environment']);

            return redirect()->route('employee.attendance.index')
                ->withErrors(['attendance' => 'Attendance could not be recorded because a required configuration is unavailable. Please contact HR.']);
        } catch (ValidationException $exception) {
            $this->flashValidationDiagnostics($request, $exception);

            throw $exception;
        }

        return redirect()->route('employee.attendance.index')->with('status', 'Check-in recorded successfully.');
    }

    public function checkOut(EmployeeAttendancePunchRequest $request): RedirectResponse
    {
        try {
            $this->punches->checkOut(
                $request->user(),
                $request->validated('latitude'),
                $request->validated('longitude'),
                $request->validated('accuracy_meters'),
            );
        } catch (InvalidArgumentException) {
            $this->flashDiagnostics($request, 'server_rejected_configuration', ['environment']);

            return redirect()->route('employee.attendance.index')
                ->withErrors(['attendance' => 'Attendance could not be recorded because a required configuration is unavailable. Please contact HR.']);
        } catch (ValidationException $exception) {
            $this->flashValidationDiagnostics($request, $exception);

            throw $exception;
        }

        return redirect()->route('employee.attendance.index')->with('status', 'Check-out recorded successfully.');
    }

    private function flashValidationDiagnostics(EmployeeAttendancePunchRequest $request, ValidationException $exception): void
    {
        $fields = array_keys($exception->errors());
        $category = match (true) {
            in_array('accuracy_meters', $fields, true) => 'server_rejected_accuracy',
            in_array('latitude', $fields, true), in_array('longitude', $fields, true) => 'server_rejected_coordinates',
            in_array('location', $fields, true) => 'server_rejected_location_boundary',
            in_array('environment', $fields, true) => 'server_rejected_location_environment',
            in_array('work_date', $fields, true) => 'server_rejected_shift_assignment',
            in_array('employee', $fields, true) => 'server_rejected_employee_account',
            default => 'server_rejected_punch_rules',
        };

        $this->flashDiagnostics($request, $category, $fields);
    }

    /** @param array<int, string> $fields */
    private function flashDiagnostics(EmployeeAttendancePunchRequest $request, string $category, array $fields): void
    {
        if (config('app.env') === 'local') {
            session()->flash('attendance_diagnostics', $request->diagnostics($category, $fields));
        }
    }
}
