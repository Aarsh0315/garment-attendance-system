@extends('layouts.app')

@section('title', 'Employee shift assignments')

@section('content')
    @include('employees.partials.feedback')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">HR Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Employee shift assignments</h1>
            <p class="mt-2 text-slate-600">Assignment start and end dates are inclusive. Ongoing assignments have no end date.</p>
        </div>
        <a class="w-fit rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white transition duration-150 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" href="{{ route('hr-admin.shift-assignments.create') }}">Assign a shift</a>
    </div>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="shift-assignment-list-heading">
        <h2 class="sr-only" id="shift-assignment-list-heading">Shift assignment history</h2>
        @if ($assignments->isEmpty())
            <p class="p-6 text-slate-600" role="status">No shift assignments have been recorded.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Employee shift assignment history</caption>
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-6 py-4" scope="col">Employee</th>
                            <th class="px-6 py-4" scope="col">Shift</th>
                            <th class="px-6 py-4" scope="col">Effective from</th>
                            <th class="px-6 py-4" scope="col">Effective through</th>
                            <th class="px-6 py-4" scope="col">Assigned by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($assignments as $assignment)
                            <tr class="transition-colors hover:bg-slate-50">
                                <th class="px-6 py-4 font-medium" scope="row">{{ $assignment->employee->employee_code }}<span class="mt-1 block text-slate-600">{{ $assignment->employee->user->name }}</span></th>
                                <td class="px-6 py-4">{{ $assignment->shift->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $assignment->effective_from->toDateString() }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $assignment->effective_to?->toDateString() ?? 'Ongoing' }}</td>
                                <td class="px-6 py-4">{{ $assignment->assignedBy->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-6 py-4">{{ $assignments->links() }}</div>
        @endif
    </section>
@endsection
