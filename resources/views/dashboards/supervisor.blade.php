@extends('layouts.app')

@section('title', 'Supervisor Dashboard')

@section('content')
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9" aria-labelledby="dashboard-heading">
        <div class="flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-800" aria-hidden="true">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-5h4m-2-2v4" /></svg>
        </div>
        <p class="mt-6 text-xs font-bold uppercase tracking-[0.16em] text-teal-800">Supervisor workspace</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950" id="dashboard-heading">Team overview</h1>
        <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">Welcome, {{ auth()->user()->name }}. Team attendance information will appear here when the assigned-team attendance view is implemented.</p>
        <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Team attendance</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">No team figures or attendance records are displayed in this preview.</p>
            </div>
            <span class="inline-flex w-fit shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200" role="status">Not connected yet</span>
        </div>
    </section>
@endsection
