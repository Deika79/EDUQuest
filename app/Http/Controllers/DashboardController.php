<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return match (true) {
            $request->user()->isAdministrator() => to_route('admin.dashboard'),
            $request->user()->isTeacher() => to_route('teacher.dashboard'),
            default => to_route('student.dashboard'),
        };
    }
}
