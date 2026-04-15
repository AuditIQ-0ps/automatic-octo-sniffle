<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
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
        if (auth()->check() && Auth::user()->role_id == 1 ) {
            if((Auth::user()->status ?? 0)!= 1)
            {

                Auth::logout();
                return redirect()->route('admin/login')->withErrors(['email'=>"Sorry, it looks like your account is inactive, kindly contact the admin for further assistance."]);
            }
            return $next($request);
        } else {

            return redirect()->route('admin/login');
        }
    }
}
