<div>
    <label class="mb-2 block text-sm font-medium" for="name">Employee name</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('name') border-rose-500 @enderror" id="name" name="name" type="text" value="{{ old('name', $employee?->user?->name) }}" maxlength="255" autocomplete="name" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
    @error('name') <p class="mt-2 text-sm text-rose-700" id="name-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="email">Assigned email address</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('email') border-rose-500 @enderror" id="email" name="email" type="email" value="{{ old('email', $employee?->user?->email) }}" maxlength="255" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
    @error('email') <p class="mt-2 text-sm text-rose-700" id="email-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="employee_code">Employee ID</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('employee_code') border-rose-500 @enderror" id="employee_code" name="employee_code" type="text" value="{{ old('employee_code', $employee?->employee_code) }}" maxlength="64" required aria-describedby="employee-code-help @error('employee_code')employee-code-error @enderror" @error('employee_code') aria-invalid="true" @enderror>
    <p class="mt-2 text-sm text-slate-500" id="employee-code-help">Leading and trailing spaces are removed; letters are stored in uppercase.</p>
    @error('employee_code') <p class="mt-2 text-sm text-rose-700" id="employee-code-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="department_id">Department</label>
    @if ($departments->isEmpty())
        <p class="mb-2 text-sm text-amber-800" role="status">There are no active departments. <a class="font-semibold underline" href="{{ route('hr-admin.departments.create') }}">Create a department first.</a></p>
    @endif
    <select class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('department_id') border-rose-500 @enderror" id="department_id" name="department_id" required @error('department_id') aria-invalid="true" aria-describedby="department-error" @enderror>
        <option value="">Select a department</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected((string) old('department_id', $employee?->department_id) === (string) $department->id)>
                {{ $department->name }}{{ $department->is_active ? '' : ' (inactive, current assignment)' }}
            </option>
        @endforeach
    </select>
    @error('department_id') <p class="mt-2 text-sm text-rose-700" id="department-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="designation">Designation</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('designation') border-rose-500 @enderror" id="designation" name="designation" type="text" value="{{ old('designation', $employee?->designation) }}" maxlength="255" required @error('designation') aria-invalid="true" aria-describedby="designation-error" @enderror>
    @error('designation') <p class="mt-2 text-sm text-rose-700" id="designation-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="joining_date">Joining date</label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('joining_date') border-rose-500 @enderror" id="joining_date" name="joining_date" type="date" value="{{ old('joining_date', $employee?->joining_date?->format('Y-m-d')) }}" required @error('joining_date') aria-invalid="true" aria-describedby="joining-date-error" @enderror>
    @error('joining_date') <p class="mt-2 text-sm text-rose-700" id="joining-date-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="mb-2 block text-sm font-medium" for="phone">Phone number <span class="font-normal text-slate-500">(optional)</span></label>
    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('phone') border-rose-500 @enderror" id="phone" name="phone" type="tel" value="{{ old('phone', $employee?->phone) }}" maxlength="32" autocomplete="tel" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
    @error('phone') <p class="mt-2 text-sm text-rose-700" id="phone-error">{{ $message }}</p> @enderror
</div>

@if ($creating)
    <div class="border-t border-slate-200 pt-5">
        <h2 class="text-lg font-semibold">Temporary login password</h2>
        <p class="mt-1 text-sm text-slate-600">The employee will be required to change this password at first login. Do not reuse or share it through this form’s messages.</p>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium" for="password">Temporary password</label>
        <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('password') border-rose-500 @enderror" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-help password-error" @enderror>
        <p class="mt-2 text-sm text-slate-500" id="password-help">Use at least 12 characters. Password fields are never repopulated after validation errors.</p>
        @error('password') <p class="mt-2 text-sm text-rose-700" id="password-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium" for="password_confirmation">Confirm temporary password</label>
        <input class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-100 @error('password_confirmation') border-rose-500 @enderror" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required @error('password_confirmation') aria-invalid="true" aria-describedby="password-confirmation-error" @enderror>
        @error('password_confirmation') <p class="mt-2 text-sm text-rose-700" id="password-confirmation-error">{{ $message }}</p> @enderror
    </div>
@endif

<div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
    <button class="rounded-xl bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" type="submit">{{ $submitLabel }}</button>
    <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ $cancelUrl }}">Cancel</a>
</div>
