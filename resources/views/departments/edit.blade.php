@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
    @include('departments.partials.feedback')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.departments.show', $department) }}">&larr; Department details</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Edit department</h1>
        <p class="mt-2 text-slate-600">Update the department name. Existing employee links are retained.</p>

        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.departments.update', $department) }}">
            @csrf
            @method('PUT')
            @include('departments.partials.form', ['department' => $department, 'submitLabel' => 'Save changes', 'cancelUrl' => route('hr-admin.departments.show', $department)])
        </form>
    </div>
@endsection
