<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendancePunchService;
use App\Support\AttendanceClock;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrAttendanceController extends Controller
{
    public function index(Request $request, AttendancePunchService $punches): View
    {
        $validated = $request->validate([
            'work_date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'in:complete,incomplete'],
        ]);

        $workDate = $validated['work_date'] ?? CarbonImmutable::now(AttendanceClock::STORAGE_TIMEZONE)
            ->setTimezone(AttendanceClock::LOCAL_TIMEZONE)
            ->toDateString();
        $search = trim($validated['search'] ?? '');

        $availableStates = AttendanceRecord::query()
            ->whereDate('work_date', $workDate)
            ->selectRaw("CASE WHEN check_in_at IS NOT NULL AND check_out_at IS NOT NULL THEN 'complete' ELSE 'incomplete' END AS attendance_state")
            ->distinct()
            ->orderBy('attendance_state')
            ->pluck('attendance_state');

        $records = AttendanceRecord::query()
            ->with(['employee.user', 'employee.department'])
            ->whereDate('work_date', $workDate)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->whereHas('employee', function ($employeeQuery) use ($like): void {
                    $employeeQuery->where('employee_code', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like): void {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        })
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'like', $like));
                });
            })
            ->when(($validated['state'] ?? null) === 'complete', fn ($query) => $query->whereNotNull('check_in_at')->whereNotNull('check_out_at'))
            ->when(($validated['state'] ?? null) === 'incomplete', fn ($query) => $query->where(function ($query): void {
                $query->whereNull('check_in_at')->orWhereNull('check_out_at');
            }))
            ->orderBy('employee_id')
            ->paginate(15)
            ->withQueryString();

        $lateStatuses = $records->getCollection()->mapWithKeys(
            fn (AttendanceRecord $record): array => [$record->getKey() => $punches->isLate($record)],
        );

        return view('hr-attendance.index', compact('records', 'workDate', 'search', 'availableStates', 'lateStatuses'));
    }

    public function show(AttendanceRecord $attendanceRecord, AttendancePunchService $punches): View
    {
        $attendanceRecord->load([
            'employee.user',
            'employee.department',
            'events' => fn ($query) => $query->with(['actor', 'officeLocation'])->orderBy('event_at'),
            'auditLogs' => fn ($query) => $query->with('actor')->orderBy('created_at'),
        ]);

        $late = $punches->isLate($attendanceRecord);

        return view('hr-attendance.show', compact('attendanceRecord', 'late'));
    }
}
