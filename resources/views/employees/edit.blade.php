@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.employees.show', $employee) }}">&larr; Employee details</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Edit employee</h1>
        <p class="mt-2 text-slate-600">Update profile details. Account role and status are managed separately.</p>

        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.employees.update', $employee) }}" autocomplete="off">
            @csrf
            @method('PUT')
            @include('employees.partials.form', ['employee' => $employee, 'creating' => false, 'departments' => $departments, 'submitLabel' => 'Save changes', 'cancelUrl' => route('hr-admin.employees.show', $employee)])
        </form>
    </div>
@endsection
