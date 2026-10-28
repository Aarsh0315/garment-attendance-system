@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.employees.index') }}">&larr; Employees</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Add employee</h1>
        <p class="mt-2 text-slate-600">Create an employee profile and login account. The account receives the Employee role.</p>

        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.employees.store') }}" autocomplete="off">
            @csrf
            @include('employees.partials.form', ['employee' => null, 'creating' => true, 'departments' => $departments, 'submitLabel' => 'Create employee', 'cancelUrl' => route('hr-admin.employees.index')])
        </form>
    </div>
@endsection
