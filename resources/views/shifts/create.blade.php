@extends('layouts.app')

@section('title', 'Add shift')

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.shifts.index') }}">&larr; Shifts</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Add shift</h1>
        <p class="mt-2 text-slate-600">Enter the approved shift schedule and grace period.</p>
        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.shifts.store') }}">
            @csrf
            @include('shifts.partials.form', ['shift' => null, 'submitLabel' => 'Create shift', 'cancelUrl' => route('hr-admin.shifts.index')])
        </form>
    </div>
@endsection
