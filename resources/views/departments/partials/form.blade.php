<div>
    <label class="mb-2 block text-sm font-medium" for="name">Department name</label>
    <input
        class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 @error('name') border-rose-500 @enderror"
        id="name"
        name="name"
        type="text"
        value="{{ old('name', $department?->name) }}"
        maxlength="255"
        required
        aria-describedby="name-help name-error"
        @error('name') aria-invalid="true" @enderror
    >
    <p class="mt-2 text-sm text-slate-500" id="name-help">Leading and trailing spaces are removed. Names are compared without regard to case.</p>
    @error('name')
        <p class="mt-2 text-sm text-rose-700" id="name-error" role="alert">{{ $message }}</p>
    @enderror
</div>

<div class="flex flex-wrap items-center gap-3">
    <button class="rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" type="submit">
        {{ $submitLabel }}
    </button>
    <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ $cancelUrl }}">
        Cancel
    </a>
</div>
