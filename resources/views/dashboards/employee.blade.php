@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="dashboard-heading">
        <div class="bg-gradient-to-r from-teal-800 to-emerald-700 px-6 py-8 text-white sm:px-9 sm:py-10">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-100">Employee workspace</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl" id="dashboard-heading">Your workday</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-teal-50 sm:text-base">Welcome, {{ auth()->user()->name }}. Your personal attendance tools will be available here as they are released.</p>
        </div>
        <div class="grid gap-5 p-6 sm:grid-cols-2 sm:p-9">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.15em] text-teal-800">Personal records</p>
                <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">Attendance history</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Record today's check-in or check-out and review your current punch status.</p>
            </div>
            <div class="flex flex-col justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                <a class="inline-flex w-fit rounded-xl bg-teal-800 px-5 py-3 font-semibold text-white transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2" href="{{ route('employee.attendance.index') }}">Open today's attendance</a>
                <p class="mt-3 text-sm leading-6 text-slate-600">Your attendance times come from saved records. Your browser location is checked by the server when you punch; GPS does not prove physical presence.</p>
            </div>
        </div>
    </section>
@endsection
