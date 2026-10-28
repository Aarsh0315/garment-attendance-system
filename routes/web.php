<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeShiftAssignmentController;
use App\Http\Controllers\HrAttendanceController;
use App\Http\Controllers\OfficeLocationController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SupervisorAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware(['auth', 'password.change.required'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change.edit');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::get('/hr-admin/dashboard', fn () => view('dashboards.hr-admin'))
        ->middleware('role:hr_admin')
        ->name('dashboards.hr-admin');

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/attendance')
        ->name('hr-admin.attendance.')
        ->controller(HrAttendanceController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{attendanceRecord}', 'show')->whereNumber('attendanceRecord')->name('show');
        });

    Route::get('/supervisor/dashboard', fn () => view('dashboards.supervisor'))
        ->middleware('role:supervisor')
        ->name('dashboards.supervisor');

    Route::get('/employee/dashboard', fn () => view('dashboards.employee'))
        ->middleware('role:employee')
        ->name('dashboards.employee');

    Route::middleware('role:employee')
        ->prefix('employee/attendance')
        ->name('employee.attendance.')
        ->controller(EmployeeAttendanceController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/check-in', 'checkIn')->name('check-in');
            Route::post('/check-out', 'checkOut')->name('check-out');
        });

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/office-locations')
        ->name('hr-admin.office-locations.')
        ->controller(OfficeLocationController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{location}', 'show')->whereNumber('location')->name('show');
            Route::get('/{location}/edit', 'edit')->whereNumber('location')->name('edit');
            Route::put('/{location}', 'update')->whereNumber('location')->name('update');
            Route::patch('/{location}/activate', 'activate')->whereNumber('location')->name('activate');
            Route::patch('/{location}/deactivate', 'deactivate')->whereNumber('location')->name('deactivate');
        });

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/shifts')
        ->name('hr-admin.shifts.')
        ->controller(ShiftController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{shift}', 'show')->name('show');
            Route::get('/{shift}/edit', 'edit')->name('edit');
            Route::put('/{shift}', 'update')->name('update');
            Route::patch('/{shift}/deactivate', 'deactivate')->name('deactivate');
            Route::patch('/{shift}/activate', 'activate')->name('activate');
        });

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/shift-assignments')
        ->name('hr-admin.shift-assignments.')
        ->controller(EmployeeShiftAssignmentController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/departments')
        ->name('hr-admin.departments.')
        ->controller(DepartmentController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{department}', 'show')->name('show');
            Route::get('/{department}/edit', 'edit')->name('edit');
            Route::put('/{department}', 'update')->name('update');
            Route::patch('/{department}/deactivate', 'deactivate')->name('deactivate');
            Route::patch('/{department}/activate', 'activate')->name('activate');
        });

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/employees')
        ->name('hr-admin.employees.')
        ->controller(EmployeeController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{employee}', 'show')->name('show');
            Route::get('/{employee}/edit', 'edit')->name('edit');
            Route::put('/{employee}', 'update')->name('update');
            Route::patch('/{employee}/deactivate', 'deactivate')->name('deactivate');
            Route::patch('/{employee}/activate', 'activate')->name('activate');
        });

    Route::middleware('role:hr_admin')
        ->prefix('hr-admin/employees/{employee}/supervisor-assignments')
        ->name('hr-admin.employees.supervisor-assignments.')
        ->controller(SupervisorAssignmentController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });
});
