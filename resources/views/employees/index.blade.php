@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    @include('employees.partials.feedback')

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">HR Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Employees</h1>
            <p class="mt-2 text-slate-600">Employee profiles and login account status.</p>
        </div>
        <a class="w-fit rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white transition duration-150 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" href="{{ route('hr-admin.employees.create') }}">Add employee</a>
    </div>

    <form class="mt-6 flex flex-col gap-3 sm:flex-row" method="GET" action="{{ route('hr-admin.employees.index') }}" role="search">
        <div class="flex-1">
            <label class="sr-only" for="employee-search">Search employees</label>
            <input class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('search') border-rose-500 @enderror" id="employee-search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Search by ID, name, email, department or designation" @error('search') aria-invalid="true" aria-describedby="employee-search-error" @enderror>
            @error('search') <p class="mt-2 text-sm text-rose-700" id="employee-search-error">{{ $message }}</p> @enderror
        </div>
        <div class="flex gap-2">
            <button class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 transition duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" type="submit">Search</button>
            @if ($search !== '')
                <a class="rounded-xl px-4 py-3 font-semibold text-indigo-700 transition duration-150 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" href="{{ route('hr-admin.employees.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="employee-list-heading">
        <h2 class="sr-only" id="employee-list-heading">Employee list</h2>
        @if ($employees->isEmpty())
            <p class="p-6 text-slate-600" role="status">{{ $search !== '' ? 'No employees match your search.' : 'No employee records have been created.' }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-6 py-4" scope="col">Employee ID</th>
                            <th class="px-6 py-4" scope="col">Name and email</th>
                            <th class="px-6 py-4" scope="col">Department</th>
                            <th class="px-6 py-4" scope="col">Designation</th>
                            <th class="px-6 py-4" scope="col">Joining date</th>
                            <th class="px-6 py-4" scope="col">Status</th>
                            <th class="px-6 py-4" scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($employees as $employee)
                            <tr class="transition-colors hover:bg-slate-50">
                                <th class="px-6 py-4 font-medium" scope="row">{{ $employee->employee_code }}</th>
                                <td class="px-6 py-4">
                                    <span class="block font-medium">{{ $employee->user->name }}</span>
                                    <span class="mt-1 block text-slate-600">{{ $employee->user->email }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $employee->department->name }}</td>
                                <td class="px-6 py-4">{{ $employee->designation }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $employee->joining_date->toDateString() }}</td>
                                <td class="px-6 py-4">
                                    @if ($employee->user->is_active)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-900">Active</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a class="font-semibold text-indigo-700 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-600" href="{{ route('hr-admin.employees.show', $employee) }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-6 py-4">{{ $employees->links() }}</div>
        @endif
    </section>
@endsection
