@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-200/70 md:grid-cols-[1.1fr_0.9fr]">
        <section class="hidden flex-col justify-between bg-indigo-950 p-10 text-white md:flex">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">Attendance Portal</p>
                <h1 class="mt-8 max-w-sm text-4xl font-semibold leading-tight">A clear view of the workday.</h1>
                <p class="mt-4 max-w-sm leading-7 text-indigo-200">Sign in with the account provided by your factory administrator.</p>
            </div>
            <p class="text-sm text-indigo-300">Secure access for factory teams</p>
        </section>

        <section class="p-7 sm:p-10">
            <p class="text-sm font-semibold text-indigo-700 md:hidden">Garment Factory Attendance</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Welcome back</h2>
            <p class="mt-2 text-sm text-slate-600">Enter your assigned email and password to continue.</p>

            <form class="mt-8 space-y-5" method="POST" action="{{ route('login.store') }}">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium" for="email">Email address</label>
                    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 @error('email') border-rose-500 @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    @error('email')
                        <p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium" for="password">Password</label>
                    <input class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 @error('password') border-rose-500 @enderror" id="password" name="password" type="password" autocomplete="current-password" required>
                    @error('password')
                        <p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button class="w-full rounded-xl bg-indigo-700 px-4 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" type="submit">Sign in</button>
            </form>

            <p class="mt-6 text-center text-xs leading-5 text-slate-500">Accounts are created by an authorized HR Admin. Public registration is unavailable.</p>
        </section>
    </div>
@endsection
