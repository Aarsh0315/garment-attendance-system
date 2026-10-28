@extends('layouts.app')

@section('title', $department->name)

@section('content')
    @include('departments.partials.feedback')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.departments.index') }}">&larr; Departments</a>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="department-heading">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Department</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight" id="department-heading">{{ $department->name }}</h1>
                </div>
                @if ($department->is_active)
                    <span class="w-fit rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-900">Active</span>
                @else
                    <span class="w-fit rounded-full bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Inactive</span>
                @endif
            </div>

            <dl class="mt-8 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Linked employee records</dt>
                    <dd class="mt-1 font-medium">{{ $department->employees_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Created</dt>
                    <dd class="mt-1 font-medium">{{ $department->created_at->toDateString() }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6">
                <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.departments.edit', $department) }}">Edit</a>
                @if ($department->is_active)
                    <form method="POST" action="{{ route('hr-admin.departments.deactivate', $department) }}" data-confirm="Deactivate this department? It will no longer be available for new employee assignments. Existing employee links and history will be retained." data-confirm-submit-label="Deactivating…">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-rose-300 px-5 py-3 font-semibold text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2" type="submit">Deactivate department</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('hr-admin.departments.activate', $department) }}" data-confirm="Reactivate this department? It will become available for new employee assignments. Existing records will be retained." data-confirm-submit-label="Activating…">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-emerald-300 px-5 py-3 font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2" type="submit">Activate department</button>
                    </form>
                @endif
            </div>
            <p class="mt-4 text-sm text-slate-600">Departments are not hard-deleted. Deactivation retains linked employee and historical records. Only active departments can be selected for new employee assignments.</p>
        </section>
    </div>
@endsection
