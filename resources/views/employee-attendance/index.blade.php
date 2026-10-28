@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900" role="alert">
            <p class="font-semibold">We could not record that punch.</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mx-auto max-w-4xl">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-teal-800">Employee attendance</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Today's attendance</h1>
        <p class="mt-2 text-slate-600">Work date: <time datetime="{{ $workDate->toDateString() }}">{{ $workDate->format('l, F j, Y') }}</time> (Asia/Kolkata)</p>

        @if ($employee === null)
            <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-950" role="status">
                Your employee profile is not available for attendance. Please contact HR.
            </section>
        @else
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="status-heading">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <h2 class="text-xl font-semibold" id="status-heading">Punch status</h2>
                        @if ($todayRecord === null)
                            <p class="mt-2 text-slate-600">No attendance punch has been recorded for today.</p>
                        @else
                            <p class="mt-2 text-slate-600">Attendance for {{ $todayRecord->work_date->format('F j, Y') }}.</p>
                        @endif
                    </div>
                    @if ($todayRecord !== null)
                        @if ($todayRecord->check_out_at !== null)
                            <span class="w-fit rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-900">Checked out</span>
                        @else
                            <span class="w-fit rounded-full bg-sky-100 px-3 py-1.5 text-xs font-semibold text-sky-900">Checked in</span>
                        @endif
                    @else
                        <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">Not checked in</span>
                    @endif
                </div>

                <dl class="mt-7 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Check-in time</dt>
                        <dd class="mt-1 text-lg font-semibold">{{ $todayRecord?->check_in_at ? \App\Support\AttendanceClock::toLocalDisplay($todayRecord->check_in_at)->format('g:i A') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Check-out time</dt>
                        <dd class="mt-1 text-lg font-semibold">{{ $todayRecord?->check_out_at ? \App\Support\AttendanceClock::toLocalDisplay($todayRecord->check_out_at)->format('g:i A') : '—' }}</dd>
                    </div>
                    @if ($todayRecord?->check_in_at)
                        <div>
                            <dt class="text-sm text-slate-500">Arrival status</dt>
                            <dd class="mt-1 font-semibold">@if ($late)<span class="text-rose-700">Late</span>@else<span class="text-emerald-800">On time</span>@endif</dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($openRecords->count() === 1 && $openRecords->first()->work_date->toDateString() !== $workDate->toDateString())
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">
                    An unfinished attendance session from {{ $openRecords->first()->work_date->format('F j, Y') }} is open. Check it out before starting another session.
                </div>
            @elseif ($hasMultipleOpenRecords)
                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
                    More than one unfinished attendance session exists. Punching is paused; please contact HR to review the records.
                </div>
            @endif

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="actions-heading">
                <h2 class="text-xl font-semibold" id="actions-heading">Record a punch</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Your browser will request your location only after you select a punch action. The server validates the reported coordinates and accuracy. GPS does not prove physical presence.</p>
                <p class="mt-3 min-h-6 text-sm font-medium text-rose-700" id="geolocation-message" role="status" aria-live="polite"></p>

                @if (config('app.env') === 'local')
                    @php($diagnostics = session('attendance_diagnostics', []))
                    <details class="mt-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" id="attendance-diagnostics">
                        <summary class="cursor-pointer font-semibold">Temporary development diagnostics</summary>
                        <p class="mt-2">These diagnostics omit all coordinates and are shown only in the local environment.</p>
                        <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                            <div><dt class="font-medium">Category</dt><dd data-diagnostic="category">{{ $diagnostics['category'] ?? 'Waiting for a location reading' }}</dd></div>
                            <div><dt class="font-medium">Browser error code</dt><dd data-diagnostic="browser_error_code">{{ $diagnostics['browser_error_code'] ?? 'None' }}</dd></div>
                            <div><dt class="font-medium">Reported accuracy</dt><dd data-diagnostic="reported_accuracy_meters">{{ isset($diagnostics['reported_accuracy_meters']) ? number_format($diagnostics['reported_accuracy_meters'], 1).' metres' : 'Not available' }}</dd></div>
                            <div><dt class="font-medium">Latitude validation</dt><dd data-diagnostic="latitude_valid">{{ isset($diagnostics['latitude_valid']) ? ($diagnostics['latitude_valid'] ? 'Pass' : 'Fail') : 'Not available' }}</dd></div>
                            <div><dt class="font-medium">Longitude validation</dt><dd data-diagnostic="longitude_valid">{{ isset($diagnostics['longitude_valid']) ? ($diagnostics['longitude_valid'] ? 'Pass' : 'Fail') : 'Not available' }}</dd></div>
                            <div><dt class="font-medium">Accuracy validation (0–100 m)</dt><dd data-diagnostic="accuracy_valid">{{ isset($diagnostics['accuracy_valid']) ? ($diagnostics['accuracy_valid'] ? 'Pass' : 'Fail') : 'Not available' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="font-medium">Server rejection</dt><dd data-diagnostic="server_rejection">{{ $diagnostics['server_rejection'] ?? 'Not submitted' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="font-medium">Server validation fields</dt><dd data-diagnostic="server_validation_fields">{{ isset($diagnostics['server_validation_fields']) ? implode(', ', $diagnostics['server_validation_fields']) : 'None' }}</dd></div>
                        </dl>
                    </details>
                @endif

                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($canCheckIn)
                        <form method="POST" action="{{ route('employee.attendance.check-in') }}" data-attendance-punch data-retry-label="Retry check-in">
                            @csrf
                            <input type="hidden" name="latitude">
                            <input type="hidden" name="longitude">
                            <input type="hidden" name="accuracy_meters">
                            <button class="rounded-xl bg-teal-800 px-6 py-3 font-semibold text-white transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 motion-reduce:transition-none" type="submit">Check in</button>
                        </form>
                    @endif
                    @if ($canCheckOut)
                        <form method="POST" action="{{ route('employee.attendance.check-out') }}" data-attendance-punch data-retry-label="Retry check-out">
                            @csrf
                            <input type="hidden" name="latitude">
                            <input type="hidden" name="longitude">
                            <input type="hidden" name="accuracy_meters">
                            <button class="rounded-xl bg-indigo-700 px-6 py-3 font-semibold text-white transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-700 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 motion-reduce:transition-none" type="submit">@if ($openRecords->first()->work_date->toDateString() === $workDate->toDateString()) Check out @else Check out open session @endif</button>
                        </form>
                    @endif
                    @if (! $canCheckIn && ! $canCheckOut && ! $hasMultipleOpenRecords && $todayRecord?->check_out_at)
                        <p class="text-sm font-medium text-emerald-800" role="status">Both punches for today are complete.</p>
                    @endif
                </div>
            </section>
        @endif
    </div>
@endsection
