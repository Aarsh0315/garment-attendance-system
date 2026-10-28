@php
    $userRole = auth()->user()->role;
    $linkClasses = $mobile
        ? 'sidebar-nav-link flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition duration-150 ease-out motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-indigo-600'
        : 'sidebar-nav-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition duration-150 ease-out motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-indigo-600';
@endphp

@if ($userRole === \App\Models\User::ROLE_HR_ADMIN)
    <a class="{{ $linkClasses }} {{ request()->routeIs('dashboards.hr-admin') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('dashboards.hr-admin') }}" aria-label="Overview" title="Overview" @if (request()->routeIs('dashboards.hr-admin')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z" /></svg>
        <span class="navigation-label">Overview</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('hr-admin.employees.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('hr-admin.employees.index') }}" aria-label="Employees" title="Employees" @if (request()->routeIs('hr-admin.employees.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-4h5m-2.5-2.5v5" /></svg>
        <span class="navigation-label">Employees</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('hr-admin.attendance.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('hr-admin.attendance.index') }}" aria-label="Attendance" title="Attendance" @if (request()->routeIs('hr-admin.attendance.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Zm3 8h3m-3 3h6" /></svg>
        <span class="navigation-label">Attendance</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('hr-admin.departments.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('hr-admin.departments.index') }}" aria-label="Departments" title="Departments" @if (request()->routeIs('hr-admin.departments.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M10 21v-4h4v4" /></svg>
        <span class="navigation-label">Departments</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('hr-admin.office-locations.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('hr-admin.office-locations.index') }}" aria-label="Office locations" title="Office locations" @if (request()->routeIs('hr-admin.office-locations.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
        <span class="navigation-label">Office locations</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('hr-admin.shifts.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('hr-admin.shifts.index') }}" aria-label="Shifts" title="Shifts" @if (request()->routeIs('hr-admin.shifts.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
        <span class="navigation-label">Shifts</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('hr-admin.shift-assignments.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('hr-admin.shift-assignments.index') }}" aria-label="Shift assignments" title="Shift assignments" @if (request()->routeIs('hr-admin.shift-assignments.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Zm3 8h3m-3 3h6" /></svg>
        <span class="navigation-label">Shift assignments</span>
    </a>
@elseif ($userRole === \App\Models\User::ROLE_SUPERVISOR)
    <a class="{{ $linkClasses }} {{ request()->routeIs('dashboards.supervisor') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('dashboards.supervisor') }}" aria-label="Team workspace" title="Team workspace" @if (request()->routeIs('dashboards.supervisor')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-5h4m-2-2v4" /></svg>
        <span class="navigation-label">Team workspace</span>
    </a>
@elseif ($userRole === \App\Models\User::ROLE_EMPLOYEE)
    <a class="{{ $linkClasses }} {{ request()->routeIs('dashboards.employee') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('dashboards.employee') }}" aria-label="My workspace" title="My workspace" @if (request()->routeIs('dashboards.employee')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path stroke-linecap="round" stroke-linejoin="round" d="M4 21v-1a8 8 0 0 1 16 0v1" /></svg>
        <span class="navigation-label">My workspace</span>
    </a>
    <a class="{{ $linkClasses }} {{ request()->routeIs('employee.attendance.*') ? 'bg-indigo-50 font-semibold text-indigo-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}" href="{{ route('employee.attendance.index') }}" aria-label="Attendance" title="Attendance" @if (request()->routeIs('employee.attendance.*')) aria-current="page" @endif>
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Zm3 8h3m-3 3h6" /></svg>
        <span class="navigation-label">Attendance</span>
    </a>
@endif
