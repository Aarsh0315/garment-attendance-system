@extends('layouts.app')

@section('title', 'Supervisor assignment history')

@section('content')
    @include('employees.partials.feedback')

    <div class="mx-auto max-w-5xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.employees.show', $employee) }}">&larr; {{ $employee->user->name }}'s employee record</a>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="assignment-history-heading">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Employee {{ $employee->employee_code }}</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight" id="assignment-history-heading">Supervisor assignment history</h1>
                    <p class="mt-2 text-sm text-slate-600">Assignment dates are inclusive. An ongoing assignment has no end date.</p>
                </div>
                @if ($employee->user->is_active)
                    <a class="rounded-xl bg-indigo-700 px-5 py-3 text-center font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.employees.supervisor-assignments.create', $employee) }}">Assign or change supervisor</a>
                @else
                    <p class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600">This employee is inactive; assignment history is read-only.</p>
                @endif
            </div>

            @if ($employee->supervisorAssignments->isEmpty())
                <p class="mt-8 rounded-xl bg-slate-50 p-5 text-sm text-slate-600">No supervisor assignments have been recorded.</p>
            @else
                <div class="mt-8 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Supervisor assignments for {{ $employee->user->name }}</caption>
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-4 py-3" scope="col">Supervisor</th>
                                <th class="px-4 py-3" scope="col">Effective from</th>
                                <th class="px-4 py-3" scope="col">Effective through</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($employee->supervisorAssignments as $assignment)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $assignment->supervisor->name }}</td>
                                    <td class="px-4 py-3">{{ $assignment->effective_from->toDateString() }}</td>
                                    <td class="px-4 py-3">{{ $assignment->effective_to?->toDateString() ?? 'Ongoing' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
