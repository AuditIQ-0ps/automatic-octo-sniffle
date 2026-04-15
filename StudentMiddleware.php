<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && Auth::user()->role_id == 4) {
            if ((Auth::user()->status ?? 0) != 1) {
                Auth::logout();
                return redirect()->route('student/login')->withErrors(['email' => "Sorry, it looks like your account is inactive, kindly contact the admin for further assistance."]);
            } else {
                $user = Auth::user();
                $user->load('student.organization');
                if ($user->student == null || $user->student->organization == null) {
                    Auth::logout();
                    return redirect()->route('student/login')->withErrors(['email' => "Sorry, something went wrong. If it persists, kindly contact the admin for further assistance. "]);
                }
            }
            return $next($request);
        } else {

            return redirect()->route('student/login');
        }
    }
}
