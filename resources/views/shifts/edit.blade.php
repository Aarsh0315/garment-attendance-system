@extends('layouts.app')

@section('title', 'Edit shift')

@section('content')
    @include('employees.partials.feedback')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.shifts.show', $shift) }}">&larr; Shift details</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Edit shift</h1>
        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.shifts.update', $shift) }}">
            @csrf
            @method('PUT')
            @include('shifts.partials.form', ['submitLabel' => 'Save changes', 'cancelUrl' => route('hr-admin.shifts.show', $shift)])
        </form>
    </div>
@endsection
