<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentHasAvatar
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isStudent() && $request->user()->avatarProfile()->doesntExist()) {
            return to_route('student.avatar.setup.edit');
        }

        return $next($request);
    }
}
