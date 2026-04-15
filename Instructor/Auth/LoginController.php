<?php

namespace App\Http\Controllers\Instructor\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::INSTRUCTOR_HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    //show login form of instructor
    public function showLoginForm(){
        return view('instructors.auth.login');
    }

    //check instructor login credentials and redirect to instructor's home
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
        $admin= User::where(['email'=>$request['email'],'role_id'=>$request['role_id']])->first();

        if ($admin!=null){
            if (Hash::check($request['password'],$admin->password))
            {
                $credentials = [
                    'email' => $request['email'],
                    'password' => $request['password'],
                ];
                if(Auth::attempt($credentials)) {
                    $admin->update([
                        'last_login_at' => Carbon::now()->toDateTimeString(),
                        'last_login_ip' => $request->getClientIp()
                    ]);
                    return redirect()->route('instructor.home');
                }
            }
        }
        return $this->sendFailedLoginResponse($request);
    }
    public function logout(Request $request)
    {
        \auth()->logout();
        $request->session()->invalidate();
        return redirect('/instructor/login');
    }
}
