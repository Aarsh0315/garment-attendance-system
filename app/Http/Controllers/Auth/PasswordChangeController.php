<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        $user = request()->user();

        if (! $user->must_change_password) {
            return redirect()->route($user->roleDashboardRoute());
        }

        return view('auth.password-change');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->validated('password'));
        $user->must_change_password = false;

        if (! $user->save()) {
            return back()->with('error', 'Your password could not be changed. Please try again.');
        }

        $request->session()->regenerate();

        return redirect()
            ->route($user->roleDashboardRoute())
            ->with('status', 'Your password has been changed successfully.');
    }
}
