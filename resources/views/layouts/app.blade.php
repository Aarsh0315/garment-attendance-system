<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Attendance Portal') · Garment Factory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased selection:bg-indigo-100 selection:text-indigo-950">
    <a class="sr-only z-50 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-indigo-800 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:outline-none focus:ring-2 focus:ring-indigo-600" href="#main-content">Skip to main content</a>

    @auth
        <div class="min-h-screen overflow-x-hidden md:flex" id="authenticated-shell">
            <button class="fixed inset-0 z-40 pointer-events-none bg-slate-950/40 opacity-0 transition-opacity duration-250 ease-out motion-reduce:transition-none md:hidden" id="navigation-backdrop" type="button" aria-label="Close navigation" aria-hidden="true" inert></button>

            <aside class="fixed inset-y-0 left-0 z-50 hidden flex w-72 shrink-0 flex-col overflow-y-auto border-r border-slate-200 bg-white shadow-xl transition-transform duration-250 ease-in-out motion-reduce:transition-none md:static md:z-auto md:w-64 md:translate-x-0 md:shadow-none" id="primary-navigation" aria-label="Primary navigation" aria-hidden="false">
                <a class="sidebar-brand-link flex items-center gap-3 border-b border-slate-100 px-6 py-6 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600" href="{{ route('dashboard') }}">
                    <span class="grid size-11 place-items-center rounded-2xl bg-indigo-700 text-sm font-bold tracking-wide text-white shadow-sm shadow-indigo-200" aria-hidden="true">GF</span>
                    <span class="sidebar-brand-copy min-w-0">
                        <span class="block truncate text-sm font-bold tracking-tight text-slate-900">Garment Factory</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Attendance workspace</span>
                    </span>
                </a>

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 md:hidden">
                    <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Navigation</span>
                    <button class="grid size-10 place-items-center rounded-xl text-slate-600 transition duration-150 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" id="navigation-close" type="button" aria-label="Close navigation">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                <div class="sidebar-links-container px-4 pt-6">
                    <p class="sidebar-section-label px-3 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Workspace</p>
                    <nav class="mt-3 space-y-1" aria-label="Main navigation">
                        @include('layouts.partials.navigation-links', ['mobile' => false])
                    </nav>
                </div>

                <div class="mt-auto border-t border-slate-100 p-4">
                    <div class="sidebar-user-summary mb-3 flex min-w-0 items-center gap-3 rounded-xl bg-slate-50 p-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="sidebar-user-copy min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                            <span class="mt-0.5 block text-xs capitalize text-slate-500">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <a class="sidebar-utility-action inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" href="{{ route('password.change.edit') }}" aria-label="Change password" title="Change password">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2" /><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" /></svg>
                            <span class="sidebar-utility-label">Password</span>
                        </a>
                        <form class="flex-1" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="sidebar-utility-action w-full rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" type="submit" aria-label="Log out" title="Log out">
                                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" /></svg>
                                <span class="sidebar-utility-label">Log out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 shadow-sm shadow-slate-900/[0.02] backdrop-blur">
                    <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button class="grid size-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-700 transition duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 motion-reduce:transition-none" id="navigation-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="Toggle navigation">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                            </button>
                            <a class="flex items-center gap-2.5 font-semibold tracking-tight md:hidden" href="{{ route('dashboard') }}">
                                <span class="grid size-9 place-items-center rounded-xl bg-indigo-700 text-xs font-bold text-white" aria-hidden="true">GF</span>
                                <span class="text-sm text-slate-900">Garment Factory</span>
                            </a>
                        </div>
                        <div class="hidden md:block">
                            <p class="text-xs font-medium uppercase tracking-[0.14em] text-slate-400">Attendance management</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-700">{{ ucwords(str_replace('_', ' ', auth()->user()->role)) }} workspace</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="mt-0.5 text-xs capitalize text-slate-500">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                            </div>
                            <span class="grid size-9 place-items-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800 sm:hidden" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-7xl px-4 py-7 sm:px-6 sm:py-9 lg:px-8" id="main-content">
                    @if (session('success'))
                        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900" role="status">{{ session('success') }}</p>
                    @endif
                    @if (session('error'))
                        <p class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900" role="alert">{{ session('error') }}</p>
                    @endif
                    @yield('content')
                </main>
                <footer class="mx-auto max-w-7xl px-4 pb-7 text-xs text-slate-400 sm:px-6 lg:px-8">
                    Internal attendance workspace
                </footer>
            </div>
        </div>
    @else
        <div class="min-h-screen bg-gradient-to-b from-white via-slate-50 to-indigo-50/40">
            <header class="border-b border-slate-200/70 bg-white/80">
                <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                    <span class="grid size-10 place-items-center rounded-xl bg-indigo-700 text-xs font-bold text-white shadow-sm" aria-hidden="true">GF</span>
                    <span>
                        <span class="block text-sm font-bold tracking-tight text-slate-900">Garment Factory</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Attendance management</span>
                    </span>
                </div>
            </header>
            <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8" id="main-content">
                @yield('content')
            </main>
        </div>
    @endauth
</body>
</html>
