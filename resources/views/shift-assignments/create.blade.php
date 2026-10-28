@extends('layouts.app')

@section('title', 'Assign employee shift')

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.shift-assignments.index') }}">&larr; Shift assignments</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Assign an employee shift</h1>
        <p class="mt-2 text-slate-600">Choose an active employee, an active shift and the inclusive assignment dates. Existing assignments are preserved; overlapping ranges are rejected.</p>

        @if ($employees->isEmpty() || $shifts->isEmpty())
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="status">
                @if ($employees->isEmpty()) No active employees are available. @endif
                @if ($shifts->isEmpty()) No active shifts are available. Create or activate a shift first. @endif
            </div>
        @endif

        <form class="mt-6 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.shift-assignments.store') }}">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-medium" for="employee_id">Employee</label>
                <select class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('employee_id') border-rose-500 @enderror" id="employee_id" name="employee_id" required @disabled($employees->isEmpty()) @error('employee_id') aria-invalid="true" aria-describedby="employee-error" @enderror>
                    <option value="">Select an active employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->employee_code }} — {{ $employee->user->name }}</option>
                    @endforeach
                </select>
                @error('employee_id') <p class="mt-2 text-sm text-rose-700" id="employee-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium" for="shift_id">Shift</label>
                <select class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('shift_id') border-rose-500 @enderror" id="shift_id" name="shift_id" required @disabled($shifts->isEmpty()) @error('shift_id') aria-invalid="true" aria-describedby="shift-error" @enderror>
                    <option value="">Select an active shift</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }}{{ $shift->ends_next_day ? ' next day' : '' }})</option>
                    @endforeach
                </select>
                @error('shift_id') <p class="mt-2 text-sm text-rose-700" id="shift-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium" for="effective_from">Effective from</label>
                    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('effective_from') border-rose-500 @enderror" id="effective_from" name="effective_from" type="date" value="{{ old('effective_from') }}" required @error('effective_from') aria-invalid="true" aria-describedby="effective-from-error" @enderror>
                    @error('effective_from') <p class="mt-2 text-sm text-rose-700" id="effective-from-error" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="effective_to">Effective through (optional)</label>
                    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('effective_to') border-rose-500 @enderror" id="effective_to" name="effective_to" type="date" value="{{ old('effective_to') }}" @error('effective_to') aria-invalid="true" aria-describedby="effective-to-error" @enderror>
                    @error('effective_to') <p class="mt-2 text-sm text-rose-700" id="effective-to-error" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-sm text-slate-600">Periods are inclusive: two assignments cannot cover the same date. Adjacent periods and gaps are allowed. Attendance processing rejects a work date without an assignment.</p>
            <div class="flex flex-wrap gap-3">
                <button class="rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400" type="submit" @disabled($employees->isEmpty() || $shifts->isEmpty())>Save assignment</button>
                <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.shift-assignments.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
