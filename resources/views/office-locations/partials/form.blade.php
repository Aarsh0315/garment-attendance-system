@php
    $fields = [
        'name' => ['Location name', 'text', '255', 'A clear label for HR to identify this boundary.'],
        'environment' => ['Environment', 'text', '16', 'Stored environment labels are trimmed and lowercased. They must exactly match the configured attendance location environment.'],
        'latitude' => ['Latitude', 'number', null, 'Decimal degrees from -90 to 90, up to 7 decimal places.'],
        'longitude' => ['Longitude', 'number', null, 'Decimal degrees from -180 to 180, up to 7 decimal places.'],
        'radius_meters' => ['Radius (metres)', 'number', null, 'Must be a positive whole number.'],
    ];
@endphp

@foreach ($fields as $field => [$label, $type, $maxlength, $help])
    <div>
        <label class="mb-2 block text-sm font-medium" for="{{ $field }}">{{ $label }}</label>
        <input
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 @error($field) border-rose-500 @enderror"
            id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
            @if ($type === 'number') step="{{ $field === 'radius_meters' ? '1' : '0.0000001' }}" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            value="{{ old($field, $location?->{$field}) }}" required
            aria-describedby="{{ $field }}-help {{ $field }}-error"
            @error($field) aria-invalid="true" @enderror
        >
        <p class="mt-2 text-sm text-slate-500" id="{{ $field }}-help">{{ $help }}</p>
        @error($field) <p class="mt-2 text-sm text-rose-700" id="{{ $field }}-error" role="alert">{{ $message }}</p> @enderror
    </div>
@endforeach

<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
    <p>Saving location details does not change its active status. Location changes affect future attendance validation; existing attendance events remain unchanged. GPS validation does not prove a person's physical presence.</p>
</div>

<div class="flex flex-wrap items-center gap-3">
    <button class="rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" type="submit">{{ $submitLabel }}</button>
    <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ $cancelUrl }}">Cancel</a>
</div>
