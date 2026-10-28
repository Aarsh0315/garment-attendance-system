@extends('layouts.app')

@section('title', 'Attendance monitoring')

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">HR Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Attendance monitoring</h1>
            <p class="mt-2 text-slate-600">Review recorded attendance and punch history. No attendance is inferred for employees without a record.</p>
        </div>
    </div>

    <form class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4 md:items-end" method="GET" action="{{ route('hr-admin.attendance.index') }}" role="search" aria-label="Filter attendance records">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="work-date">Work date</label>
            <input class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100" id="work-date" name="work_date" type="date" value="{{ $workDate }}" required>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="attendance-state">Punch state</label>
            <select class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100" id="attendance-state" name="state">
                <option value="">All recorded states</option>
                @foreach ($availableStates as $availableState)
                    <option value="{{ $availableState }}" @selected(request('state') === $availableState)>{{ $availableState === 'complete' ? 'Complete punches' : 'Incomplete punches' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="attendance-search">Employee search</label>
            <input class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('search') border-rose-500 @enderror" id="attendance-search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Code, name, email or department" @error('search') aria-invalid="true" @enderror>
        </div>
        <div class="flex gap-2">
            <button class="rounded-xl bg-indigo-700 px-5 py-2.5 font-semibold text-white transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" type="submit">Apply filters</button>
            <a class="rounded-xl px-4 py-2.5 font-semibold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" href="{{ route('hr-admin.attendance.index') }}">Reset</a>
        </div>
        @error('work_date') <p class="text-sm text-rose-700 md:col-span-4" role="alert">{{ $message }}</p> @enderror
        @error('state') <p class="text-sm text-rose-700 md:col-span-4" role="alert">{{ $message }}</p> @enderror
        @error('search') <p class="text-sm text-rose-700 md:col-span-4" role="alert">{{ $message }}</p> @enderror
    </form>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="attendance-list-heading">
        <h2 class="sr-only" id="attendance-list-heading">Attendance records for {{ $workDate }}</h2>
        @if ($records->isEmpty())
            <div class="p-8 text-center" role="status">
                <h3 class="text-lg font-semibold text-slate-900">No matching attendance records</h3>
                <p class="mt-2 text-sm text-slate-600">Try another date or adjust your filters. This list includes recorded entries only.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4" scope="col">Employee ID</th>
                            <th class="px-5 py-4" scope="col">Employee</th>
                            <th class="px-5 py-4" scope="col">Department</th>
                            <th class="whitespace-nowrap px-5 py-4" scope="col">Work date</th>
                            <th class="whitespace-nowrap px-5 py-4" scope="col">Check-in</th>
                            <th class="whitespace-nowrap px-5 py-4" scope="col">Check-out</th>
                            <th class="px-5 py-4" scope="col">Late</th>
                            <th class="px-5 py-4" scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($records as $record)
                            <tr class="transition-colors hover:bg-slate-50">
                                <th class="whitespace-nowrap px-5 py-4 font-semibold" scope="row">{{ $record->employee->employee_code }}</th>
                                <td class="px-5 py-4"><span class="block font-medium text-slate-900">{{ $record->employee->user->name }}</span><span class="mt-1 block text-slate-600">{{ $record->employee->user->email }}</span></td>
                                <td class="px-5 py-4">{{ $record->employee->department->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $record->work_date->format('Y-m-d') }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $record->check_in_at ? \App\Support\AttendanceClock::toLocalDisplay($record->check_in_at)->format('g:i A') : '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $record->check_out_at ? \App\Support\AttendanceClock::toLocalDisplay($record->check_out_at)->format('g:i A') : '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($lateStatuses[$record->id] === null)
                                        <span class="text-slate-500">—</span>
                                    @elseif ($lateStatuses[$record->id])
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">Late</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">On time</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right"><a class="font-semibold text-indigo-700 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-600" href="{{ route('hr-admin.attendance.show', $record) }}">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $records->links() }}</div>
        @endif
    </section>
@endsection
