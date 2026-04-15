<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorMiddleware
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
//        dd(Auth::user());
        if (auth()->check() && Auth::user()->role_id == 3) {
            if ((Auth::user()->status ?? 0) != 1) {
                Auth::logout();
                return redirect()->route('instructor/login')->withErrors(['email' => "Sorry, it looks like your account is inactive, kindly contact the admin for further assistance."]);
            } else {
                $user = Auth::user();
                $user->load('instructor.organization');
                if ($user->instructor == null || $user->instructor->organization == null) {
                    Auth::logout();
                    return redirect()->route('instructor/login')->withErrors(['email' => "Sorry, something went wrong. If it persists, kindly contact the admin for further assistance. "]);
                }
            }
            return $next($request);
        } else {

            return redirect()->route('instructor/login');
        }
    }
}
