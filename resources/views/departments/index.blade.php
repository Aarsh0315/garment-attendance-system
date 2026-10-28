@extends('layouts.app')

@section('title', 'Departments')

@section('content')
    @include('departments.partials.feedback')

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">HR Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Departments</h1>
            <p class="mt-2 text-slate-600">Manage factory departments. Inactive departments remain available for historical records.</p>
        </div>
        <a class="w-fit rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.departments.create') }}">Add department</a>
    </div>

    <form class="mt-6 flex flex-col gap-3 sm:flex-row" method="GET" action="{{ route('hr-admin.departments.index') }}" role="search">
        <div class="flex-1">
            <label class="sr-only" for="department-search">Search departments</label>
            <input class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('search') border-rose-500 @enderror" id="department-search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Search department names" @error('search') aria-invalid="true" aria-describedby="department-search-error" @enderror>
            @error('search') <p class="mt-2 text-sm text-rose-700" id="department-search-error">{{ $message }}</p> @enderror
        </div>
        <div class="flex gap-2">
            <button class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 transition duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" type="submit">Search</button>
            @if ($search !== '')
                <a class="rounded-xl px-4 py-3 font-semibold text-indigo-700 transition duration-150 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" href="{{ route('hr-admin.departments.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="department-list-heading">
        <h2 class="sr-only" id="department-list-heading">Department list</h2>
        @if ($departments->isEmpty())
            <p class="p-6 text-slate-600" role="status">{{ $search !== '' ? 'No departments match your search.' : 'No departments have been created.' }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-6 py-4" scope="col">Department Name</th>
                            <th class="px-6 py-4" scope="col">Status</th>
                            <th class="px-6 py-4" scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($departments as $department)
                            <tr class="transition-colors hover:bg-slate-50">
                                <th class="px-6 py-4 font-medium" scope="row">{{ $department->name }}</th>
                                <td class="px-6 py-4">
                                    @if ($department->is_active)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-900">Active</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a class="font-semibold text-indigo-700 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-600" href="{{ route('hr-admin.departments.show', $department) }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-6 py-4">{{ $departments->links() }}</div>
        @endif
    </section>
@endsection
