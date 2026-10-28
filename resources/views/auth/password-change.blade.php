@extends('layouts.app')

@section('title', 'Change password')

@section('content')
    <section class="mx-auto max-w-xl rounded-3xl bg-white p-7 shadow-xl shadow-slate-200/70 sm:p-10" aria-labelledby="password-change-heading">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Account security</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight" id="password-change-heading">Change your password</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">You must choose a new password before continuing. Use at least 12 characters.</p>

        @if (session('error'))
            <p class="mt-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-800" role="alert">{{ session('error') }}</p>
        @endif

        <form class="mt-8 space-y-5" method="POST" action="{{ route('password.change.update') }}">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-medium" for="current_password">Current password</label>
                <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 @error('current_password') border-rose-500 @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus aria-describedby="current_password-error">
                @error('current_password')
                    <p class="mt-2 text-sm text-rose-700" id="current_password-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium" for="password">New password</label>
                <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 @error('password') border-rose-500 @enderror" id="password" name="password" type="password" minlength="12" autocomplete="new-password" required aria-describedby="password-help password-error">
                <p class="mt-2 text-xs text-slate-500" id="password-help">Use at least 12 characters.</p>
                @error('password')
                    <p class="mt-2 text-sm text-rose-700" id="password-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium" for="password_confirmation">Confirm new password</label>
                <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100" id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
            </div>

            <button class="w-full rounded-xl bg-indigo-700 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" type="submit">Save new password</button>
        </form>
    </section>
@endsection
