@extends('layouts.app')

@section('title', 'Assign a supervisor')

@section('content')
    @include('employees.partials.feedback')

    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.employees.supervisor-assignments.index', $employee) }}">&larr; Assignment history</a>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="assignment-form-heading">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Employee {{ $employee->employee_code }}</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight" id="assignment-form-heading">Assign or change supervisor</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Choose when the selected supervisor's assignment begins. If there is an ongoing assignment, it will end the day before this date. A change after a completed assignment must begin the next day.</p>

            @if ($currentAssignment)
                <p class="mt-5 rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900">Current supervisor: <strong>{{ $currentAssignment->supervisor->name }}</strong>, assigned from {{ $currentAssignment->effective_from->toDateString() }}. The previous assignment will be retained in history.</p>
            @elseif ($nextStartDate)
                <p class="mt-5 rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900">The next assignment must begin on <strong>{{ $nextStartDate }}</strong> to continue after the previous assignment.</p>
            @else
                <p class="mt-5 rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900">This employee has no previous assignment. Choose the first assignment's start date.</p>
            @endif

            @if ($supervisors->isEmpty())
                <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">There are no active Supervisor accounts available. An HR Admin must activate or create a Supervisor account before an assignment can be made.</p>
            @else
                <form class="mt-7 space-y-5" method="POST" action="{{ route('hr-admin.employees.supervisor-assignments.store', $employee) }}">
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-medium" for="supervisor_user_id">Supervisor</label>
                        <select class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('supervisor_user_id') border-rose-500 @enderror" id="supervisor_user_id" name="supervisor_user_id" required @error('supervisor_user_id') aria-invalid="true" aria-describedby="supervisor-error" @enderror>
                            <option value="">Select a supervisor</option>
                            @foreach ($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" @selected(old('supervisor_user_id') == $supervisor->id)>{{ $supervisor->name }} ({{ $supervisor->email }})</option>
                            @endforeach
                        </select>
                        @error('supervisor_user_id') <p class="mt-2 text-sm text-rose-700" id="supervisor-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium" for="effective_from">Assignment start date</label>
                        <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('effective_from') border-rose-500 @enderror" id="effective_from" name="effective_from" type="date" value="{{ old('effective_from', $nextStartDate) }}" required @error('effective_from') aria-invalid="true" aria-describedby="effective-from-error" @enderror>
                        @error('effective_from') <p class="mt-2 text-sm text-rose-700" id="effective-from-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button class="rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400" type="submit" @disabled($supervisors->isEmpty())>Save assignment</button>
                        <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.employees.supervisor-assignments.index', $employee) }}">Cancel</a>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection
