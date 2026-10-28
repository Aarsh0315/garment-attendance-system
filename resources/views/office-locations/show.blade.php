@extends('layouts.app')

@section('title', $location->name)

@section('content')
    @include('office-locations.partials.feedback')
    <div class="mx-auto max-w-3xl">
        <a class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:underline" href="{{ route('hr-admin.office-locations.index') }}">&larr; Office locations</a>
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="location-heading">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div><p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Office location</p><h1 class="mt-2 text-3xl font-semibold tracking-tight" id="location-heading">{{ $location->name }}</h1></div>
                @if ($location->is_active)<span class="w-fit rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-900">Active</span>@else<span class="w-fit rounded-full bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Inactive</span>@endif
            </div>
            <dl class="mt-8 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Environment</dt><dd class="mt-1 font-medium">{{ $location->environment }}</dd></div>
                <div><dt class="text-sm text-slate-500">Radius</dt><dd class="mt-1 font-medium">{{ number_format($location->radius_meters) }} metres</dd></div>
                <div><dt class="text-sm text-slate-500">Latitude</dt><dd class="mt-1 font-medium">{{ $location->latitude }}</dd></div>
                <div><dt class="text-sm text-slate-500">Longitude</dt><dd class="mt-1 font-medium">{{ $location->longitude }}</dd></div>
            </dl>
            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6">
                <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('hr-admin.office-locations.edit', $location->id) }}">Edit details</a>
                @if ($location->is_active)
                    <form method="POST" action="{{ route('hr-admin.office-locations.deactivate', $location->id) }}" data-confirm="Deactivate this office location? Future attendance validation will no longer use it. Existing attendance history will be retained." data-confirm-submit-label="Deactivating…">@csrf @method('PATCH')<button class="rounded-xl border border-rose-300 px-5 py-3 font-semibold text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2" type="submit">Deactivate location</button></form>
                @else
                    <form method="POST" action="{{ route('hr-admin.office-locations.activate', $location->id) }}" data-confirm="Activate this office location? It will be used for future attendance validation in this exact environment." data-confirm-submit-label="Activating…">@csrf @method('PATCH')<button class="rounded-xl border border-emerald-300 px-5 py-3 font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2" type="submit">Activate location</button></form>
                @endif
            </div>
            <p class="mt-4 text-sm text-slate-600">Location changes affect future attendance validation. Existing attendance events are retained. GPS validation does not prove physical presence.</p>
        </section>
    </div>
@endsection
