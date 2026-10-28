@extends('layouts.app')

@section('title', 'HR Admin Dashboard')

@section('content')
    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 px-6 py-8 text-white shadow-lg shadow-indigo-950/10 sm:px-9 sm:py-10" aria-labelledby="dashboard-heading">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-200">HR Admin workspace</p>
        <div class="mt-3 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl" id="dashboard-heading">Factory administration</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-indigo-100 sm:text-base">Welcome, {{ auth()->user()->name }}. Manage employee and department records from one place.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-indigo-50">
                <span class="size-1.5 rounded-full bg-emerald-300" aria-hidden="true"></span>
                Administration workspace
            </span>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="people-heading">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-700">People</p>
                <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900" id="people-heading">Administration shortcuts</h2>
            </div>
            <p class="text-sm text-slate-500">Choose a workspace to continue.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <a class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.employees.index') }}">
                <span class="grid size-11 place-items-center rounded-xl bg-indigo-50 text-indigo-700 transition group-hover:bg-indigo-100" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-4h5m-2.5-2.5v5" /></svg>
                </span>
                <h3 class="mt-5 text-lg font-semibold text-slate-900">Employees</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Create and maintain employee profiles and accounts.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">Open employee management <span aria-hidden="true">→</span></span>
            </a>

            <a class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.departments.index') }}">
                <span class="grid size-11 place-items-center rounded-xl bg-teal-50 text-teal-800 transition group-hover:bg-teal-100" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M10 21v-4h4v4" /></svg>
                </span>
                <h3 class="mt-5 text-lg font-semibold text-slate-900">Departments</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Review department records and their active status.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">Open department management <span aria-hidden="true">→</span></span>
            </a>

            <section class="rounded-2xl border border-dashed border-slate-300 bg-slate-100/70 p-6" aria-labelledby="attendance-tools-heading">
                <span class="grid size-11 place-items-center rounded-xl bg-white text-slate-500" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                </span>
                <h3 class="mt-5 text-lg font-semibold text-slate-800" id="attendance-tools-heading">Attendance tools</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Attendance review and reporting are not connected to this dashboard yet.</p>
                <span class="mt-5 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600" role="status">Coming in a later stage</span>
            </section>
        </div>
    </section>
@endsection
