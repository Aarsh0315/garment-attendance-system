@extends('layouts.app')

@section('title', 'Add Department')

@section('content')
    @include('departments.partials.feedback')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.departments.index') }}">&larr; Departments</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Add department</h1>
        <p class="mt-2 text-slate-600">Create a department using its approved factory name.</p>

        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.departments.store') }}">
            @csrf
            @include('departments.partials.form', ['department' => null, 'submitLabel' => 'Create department', 'cancelUrl' => route('hr-admin.departments.index')])
        </form>
    </div>
@endsection
