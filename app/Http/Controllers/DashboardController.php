<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_active, 403);

        return redirect()->route($request->user()->roleDashboardRoute());
    }
}
