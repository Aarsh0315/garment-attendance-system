@extends('layouts.app')

@section('title', $employee->user->name)

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-4xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.employees.index') }}">&larr; Employees</a>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="employee-heading">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Employee {{ $employee->employee_code }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight" id="employee-heading">{{ $employee->user->name }}</h1>
                    <p class="mt-2 text-slate-600">{{ $employee->user->email }}</p>
                </div>
                @if ($employee->user->is_active)
                    <span class="w-fit rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-900">Active account</span>
                @else
                    <span class="w-fit rounded-full bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Inactive account</span>
                @endif
            </div>

            <dl class="mt-8 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Department</dt><dd class="mt-1 font-medium">{{ $employee->department->name }}@unless ($employee->department->is_active) <span class="text-sm font-normal text-slate-600">(inactive)</span>@endunless</dd></div>
                <div><dt class="text-sm text-slate-500">Designation</dt><dd class="mt-1 font-medium">{{ $employee->designation }}</dd></div>
                <div><dt class="text-sm text-slate-500">Joining date</dt><dd class="mt-1 font-medium">{{ $employee->joining_date->toDateString() }}</dd></div>
                <div><dt class="text-sm text-slate-500">Phone</dt><dd class="mt-1 font-medium">{{ $employee->phone ?: 'Not provided' }}</dd></div>
            </dl>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-lg font-semibold">Supervisor assignment history</h2>
                @if ($employee->supervisorAssignments->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">No supervisor assignments have been recorded.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                                <tr><th class="px-4 py-3" scope="col">Supervisor</th><th class="px-4 py-3" scope="col">From</th><th class="px-4 py-3" scope="col">Through</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($employee->supervisorAssignments as $assignment)
                                    <tr>
                                        <td class="px-4 py-3">{{ $assignment->supervisor->name }}</td>
                                        <td class="px-4 py-3">{{ $assignment->effective_from->toDateString() }}</td>
                                        <td class="px-4 py-3">{{ $assignment->effective_to?->toDateString() ?? 'Ongoing' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Shift assignment history</h2>
                    <a class="rounded-xl border border-indigo-300 px-4 py-2 font-semibold text-indigo-800 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.shift-assignments.index') }}">Manage shift assignments</a>
                </div>
                @if ($employee->shiftAssignments->isEmpty())
                    <p class="mt-2 text-sm text-slate-600">No shift assignments have been recorded.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                                <tr><th class="px-4 py-3" scope="col">Shift</th><th class="px-4 py-3" scope="col">Effective from</th><th class="px-4 py-3" scope="col">Effective through</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($employee->shiftAssignments as $assignment)
                                    <tr>
                                        <td class="px-4 py-3 font-medium">{{ $assignment->shift->name }}</td>
                                        <td class="px-4 py-3">{{ $assignment->effective_from->toDateString() }}</td>
                                        <td class="px-4 py-3">{{ $assignment->effective_to?->toDateString() ?? 'Ongoing' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6">
                <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.employees.edit', $employee) }}">Edit employee</a>
                <a class="rounded-xl border border-indigo-300 px-5 py-3 font-semibold text-indigo-800 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.employees.supervisor-assignments.index', $employee) }}">Manage supervisor assignments</a>
            </div>
        </section>

        @if ($employee->user->is_active)
            <section class="mt-6 rounded-2xl border border-rose-200 bg-white p-6 shadow-sm" aria-labelledby="deactivate-heading">
                <h2 class="text-lg font-semibold text-rose-900" id="deactivate-heading">Deactivate employee</h2>
                <p class="mt-2 text-sm text-slate-600">The login account will be disabled. The employee profile and linked history will be retained. The current supervisor assignment, if any, will end on the selected date.</p>
                <form class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end" method="POST" action="{{ route('hr-admin.employees.deactivate', $employee) }}" data-confirm="Deactivate this employee? Login access will be disabled, and the employee profile and linked history will be retained." data-confirm-submit-label="Deactivating…">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="mb-2 block text-sm font-medium" for="effective_date">Effective deactivation date</label>
                        <input class="rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('effective_date') border-rose-500 @enderror" id="effective_date" name="effective_date" type="date" value="{{ old('effective_date') }}" required @error('effective_date') aria-invalid="true" aria-describedby="effective-date-error" @enderror>
                        @error('effective_date') <p class="mt-2 text-sm text-rose-700" id="effective-date-error">{{ $message }}</p> @enderror
                    </div>
                    <button class="rounded-xl bg-rose-700 px-5 py-3 font-semibold text-white transition duration-150 hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2 motion-reduce:transition-none" type="submit">Deactivate account</button>
                </form>
            </section>
        @else
            <section class="mt-6 rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm" aria-labelledby="activate-heading">
                <h2 class="text-lg font-semibold text-emerald-900" id="activate-heading">Reactivate employee</h2>
                <p class="mt-2 text-sm text-slate-600">This enables the existing login account. The employee profile and historical attendance and assignments will remain unchanged. No supervisor or shift assignment will be created.</p>
                <form class="mt-5" method="POST" action="{{ route('hr-admin.employees.activate', $employee) }}" data-confirm="Reactivate this employee's existing account? Their profile and history will be retained." data-confirm-submit-label="Activating…">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white transition duration-150 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 motion-reduce:transition-none" type="submit">Activate account</button>
                </form>
            </section>
        @endif
    </div>
@endsection
