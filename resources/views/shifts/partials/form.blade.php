<div>
    <label class="mb-2 block text-sm font-medium" for="name">Shift name</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('name') border-rose-500 @enderror" id="name" name="name" type="text" value="{{ old('name', $shift?->name) }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
    @error('name') <p class="mt-2 text-sm text-rose-700" id="name-error" role="alert">{{ $message }}</p> @enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-medium" for="start_time">Scheduled start time</label>
        <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('start_time') border-rose-500 @enderror" id="start_time" name="start_time" type="time" value="{{ old('start_time', $shift ? substr($shift->start_time, 0, 5) : '') }}" required @error('start_time') aria-invalid="true" aria-describedby="start-time-error" @enderror>
        @error('start_time') <p class="mt-2 text-sm text-rose-700" id="start-time-error" role="alert">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium" for="end_time">Scheduled end time</label>
        <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('end_time') border-rose-500 @enderror" id="end_time" name="end_time" type="time" value="{{ old('end_time', $shift ? substr($shift->end_time, 0, 5) : '') }}" required @error('end_time') aria-invalid="true" aria-describedby="end-time-error" @enderror>
        @error('end_time') <p class="mt-2 text-sm text-rose-700" id="end-time-error" role="alert">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="late_grace_minutes">Late grace period (minutes)</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('late_grace_minutes') border-rose-500 @enderror" id="late_grace_minutes" name="late_grace_minutes" type="number" min="0" max="65535" step="1" value="{{ old('late_grace_minutes', $shift?->late_grace_minutes ?? 0) }}" required @error('late_grace_minutes') aria-invalid="true" aria-describedby="grace-error" @enderror>
    @error('late_grace_minutes') <p class="mt-2 text-sm text-rose-700" id="grace-error" role="alert">{{ $message }}</p> @enderror
</div>

<div class="space-y-2">
    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
        <input class="mt-1 rounded border-slate-300 text-indigo-700 focus:ring-indigo-600" name="ends_next_day" type="checkbox" value="1" @checked(old('ends_next_day', $shift?->ends_next_day ?? false))>
        <span><span class="block font-medium">Ends the next day</span><span class="mt-1 block text-sm text-slate-600">This records the schedule setting only. Overnight attendance processing is not currently supported.</span></span>
    </label>
    @error('ends_next_day') <p class="text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
</div>

@if ($shift && $shift->employee_assignments_count > 0)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-sm text-amber-900">This shift has {{ $shift->employee_assignments_count }} linked assignment(s). Changes to its configuration, including schedule fields, can affect future work dates for those assignments. Assignment history itself will not be rewritten.</p>
        <label class="mt-3 flex items-start gap-3 text-sm font-medium text-amber-950" for="confirm_assignment_impact">
            <input class="mt-1 rounded border-amber-400 text-indigo-700 focus:ring-indigo-600" id="confirm_assignment_impact" name="confirm_assignment_impact" type="checkbox" value="1" @checked(old('confirm_assignment_impact')) required @error('confirm_assignment_impact') aria-invalid="true" aria-describedby="assignment-impact-error" @enderror>
            <span>I understand these edits may affect linked assignments on future work dates.</span>
        </label>
        @error('confirm_assignment_impact') <p class="mt-2 text-sm text-rose-700" id="assignment-impact-error" role="alert">{{ $message }}</p> @enderror
    </div>
@endif

<div class="flex flex-wrap gap-3">
    <button class="rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white transition duration-150 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" type="submit">{{ $submitLabel }}</button>
    <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ $cancelUrl }}">Cancel</a>
</div>
