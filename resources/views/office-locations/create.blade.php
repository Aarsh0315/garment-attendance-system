@extends('layouts.app')

@section('title', 'Add Office Location')

@section('content')
    @include('office-locations.partials.feedback')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.office-locations.index') }}">&larr; Office locations</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Add office location</h1>
        <p class="mt-2 text-slate-600">Enter approved boundary details. A new location starts inactive and must be activated separately.</p>
        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.office-locations.store') }}">
            @csrf
            @include('office-locations.partials.form', ['location' => null, 'submitLabel' => 'Create location', 'cancelUrl' => route('hr-admin.office-locations.index')])
        </form>
    </div>
@endsection
