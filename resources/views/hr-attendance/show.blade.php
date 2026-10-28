@extends('layouts.app')

@section('title', 'Attendance record')

@section('content')
    <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-600" href="{{ route('hr-admin.attendance.index', ['work_date' => $attendanceRecord->work_date->toDateString()]) }}">← Back to attendance</a>

    <div class="mt-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Attendance record</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $attendanceRecord->employee->user->name }}</h1>
            <p class="mt-2 text-slate-600">{{ $attendanceRecord->employee->employee_code }} · {{ $attendanceRecord->employee->department->name }} · {{ $attendanceRecord->work_date->format('F j, Y') }}</p>
        </div>
        <span class="w-fit rounded-full px-3 py-1.5 text-xs font-semibold {{ $attendanceRecord->check_in_at && $attendanceRecord->check_out_at ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900' }}">
            {{ $attendanceRecord->check_in_at && $attendanceRecord->check_out_at ? 'Complete punches' : 'Incomplete punches' }}
        </span>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="punch-details-heading">
        <h2 class="text-xl font-semibold" id="punch-details-heading">Persisted punch details</h2>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-sm text-slate-500">Employee email</dt><dd class="mt-1 break-all font-medium">{{ $attendanceRecord->employee->user->email }}</dd></div>
            <div><dt class="text-sm text-slate-500">Check-in</dt><dd class="mt-1 font-medium">{{ $attendanceRecord->check_in_at ? \App\Support\AttendanceClock::toLocalDisplay($attendanceRecord->check_in_at)->format('M j, Y g:i:s A') : 'Not recorded' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Check-out</dt><dd class="mt-1 font-medium">{{ $attendanceRecord->check_out_at ? \App\Support\AttendanceClock::toLocalDisplay($attendanceRecord->check_out_at)->format('M j, Y g:i:s A') : 'Not recorded' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Arrival</dt><dd class="mt-1 font-semibold">@if ($late === null) — @elseif ($late)<span class="text-rose-700">Late</span>@else<span class="text-emerald-800">On time</span>@endif</dd></div>
        </dl>
        <p class="mt-5 text-xs text-slate-500">Punch instants are shown in Asia/Kolkata. This read-only page does not calculate absence, breaks, overtime, or payroll.</p>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="events-heading">
        <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-xl font-semibold" id="events-heading">Punch event history</h2><p class="mt-1 text-sm text-slate-600">Persisted events associated with this record.</p></div>
        @if ($attendanceRecord->events->isEmpty())
            <p class="p-6 text-sm text-slate-600" role="status">No punch events are linked to this record.</p>
        @else
            <div class="divide-y divide-slate-200">
                @foreach ($attendanceRecord->events as $event)
                    <article class="grid gap-3 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Event</p><p class="mt-1 font-medium">{{ str_replace('_', ' ', ucfirst($event->event_type)) }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Result and source</p><p class="mt-1">{{ ucfirst($event->outcome) }} · {{ $event->source }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recorded at</p><p class="mt-1">{{ \App\Support\AttendanceClock::toLocalDisplay($event->event_at)->format('M j, Y g:i:s A') }}</p></div>
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actor / location</p><p class="mt-1">{{ $event->actor?->name ?? 'Unavailable' }}{{ $event->officeLocation ? ' · '.$event->officeLocation->name : '' }}</p></div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="audit-heading">
        <div class="border-b border-slate-200 px-6 py-5"><h2 class="text-xl font-semibold" id="audit-heading">Administrative audit history</h2><p class="mt-1 text-sm text-slate-600">Audit entries available for this attendance record.</p></div>
        @if ($attendanceRecord->auditLogs->isEmpty())
            <p class="p-6 text-sm text-slate-600" role="status">No administrative audit entries are linked to this record.</p>
        @else
            <div class="divide-y divide-slate-200">
                @foreach ($attendanceRecord->auditLogs as $audit)
                    <article class="px-6 py-5">
                        <div class="flex flex-col justify-between gap-1 sm:flex-row"><h3 class="font-semibold">{{ $audit->action }}</h3><time class="text-sm text-slate-500">{{ $audit->created_at?->format('M j, Y g:i A') }}</time></div>
                        <p class="mt-2 text-sm text-slate-700">{{ $audit->reason }}</p>
                        <p class="mt-2 text-sm text-slate-600">Recorded by {{ $audit->actor?->name ?? 'Unavailable' }}</p>
                        @if ($audit->before_values || $audit->after_values)
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                @if ($audit->before_values)<div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Before</dt><dd class="mt-1 overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs"><pre class="whitespace-pre-wrap">{{ json_encode($audit->before_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></dd></div>@endif
                                @if ($audit->after_values)<div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">After</dt><dd class="mt-1 overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs"><pre class="whitespace-pre-wrap">{{ json_encode($audit->after_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></dd></div>@endif
                            </dl>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
