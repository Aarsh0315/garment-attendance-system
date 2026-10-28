@extends('layouts.app')

@section('title', $shift->name)

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.shifts.index') }}">&larr; Shifts</a>
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="shift-heading">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Shift schedule</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight" id="shift-heading">{{ $shift->name }}</h1>
                </div>
                @if ($shift->is_active)
                    <span class="w-fit rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-900">Active</span>
                @else
                    <span class="w-fit rounded-full bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Inactive</span>
                @endif
            </div>
            <dl class="mt-8 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Scheduled start</dt><dd class="mt-1 font-medium">{{ substr($shift->start_time, 0, 5) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Scheduled end</dt><dd class="mt-1 font-medium">{{ substr($shift->end_time, 0, 5) }}{{ $shift->ends_next_day ? ' (next day)' : '' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Late grace period</dt><dd class="mt-1 font-medium">{{ $shift->late_grace_minutes }} minutes</dd></div>
                <div><dt class="text-sm text-slate-500">Linked assignments</dt><dd class="mt-1 font-medium">{{ $shift->employee_assignments_count }}</dd></div>
            </dl>
            @if ($shift->ends_next_day)
                <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Overnight is configured for this shift, but attendance punch processing currently rejects overnight shifts. No overnight work-date rule is applied here.</p>
            @endif
            <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">Deactivating a shift prevents new assignments. Existing assignments and attendance history remain unchanged and are not cancelled.</p>
            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6">
                <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.shifts.edit', $shift) }}">Edit shift</a>
                @if ($shift->is_active)
                    <form method="POST" action="{{ route('hr-admin.shifts.deactivate', $shift) }}" data-confirm="Deactivate this shift? Existing assignments will remain unchanged; no new assignments can use this shift." data-confirm-submit-label="Deactivating…">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-rose-300 px-5 py-3 font-semibold text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2" type="submit">Deactivate shift</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('hr-admin.shifts.activate', $shift) }}" data-confirm="Reactivate this shift? It will be available for new assignments." data-confirm-submit-label="Activating…">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-emerald-300 px-5 py-3 font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2" type="submit">Activate shift</button>
                    </form>
                @endif
                <a class="rounded-xl border border-indigo-300 px-5 py-3 font-semibold text-indigo-800 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.shift-assignments.index') }}">View shift assignments</a>
            </div>
        </section>
    </div>
@endsection
