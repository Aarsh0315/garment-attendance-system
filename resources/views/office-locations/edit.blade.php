@extends('layouts.app')

@section('title', 'Edit Office Location')

@section('content')
    @include('office-locations.partials.feedback')
    <div class="mx-auto max-w-2xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.office-locations.show', $location->id) }}">&larr; Location details</a>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Edit office location</h1>
        <p class="mt-2 text-slate-600">Changing these details affects future attendance validation. Activation status is managed separately.</p>
        <form class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" method="POST" action="{{ route('hr-admin.office-locations.update', $location->id) }}">
            @csrf
            @method('PUT')
            @include('office-locations.partials.form', ['location' => $location, 'submitLabel' => 'Save changes', 'cancelUrl' => route('hr-admin.office-locations.show', $location->id)])
        </form>
    </div>
@endsection
