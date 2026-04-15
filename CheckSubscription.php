<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
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
        $user = $request->user();
//        dd($user);
        if ($user->role_id == 2) {
            $user->load('organization.subscription');
//        dd($user);
            if ($user->organization == null || $user->organization->subscription == null) {
                return redirect(route('organization.subscribe.index'));
            }
        } else if ($user->role_id == 3) {
            $user->load('instructor.organization.subscription');
            if ($user->instructor == null || $user->instructor->organization == null || $user->instructor->organization->subscription == null) {
                return redirect(route('expire.subscription'));
            }
        } else if ($user->role_id == 4) {
            $user->load('student.organization.subscription');
            if ($user->student == null || $user->student->organization == null || $user->student->organization->subscription == null) {
                return redirect(route('expire.subscription'));
            }
        }
        return $next($request);
    }
}
