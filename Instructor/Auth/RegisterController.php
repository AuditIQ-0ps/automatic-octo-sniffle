<?php

namespace App\Http\Controllers\Instructor\Auth;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Organization;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::STUDENT_HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    //validation for input fields
    protected function validator(array $data)
    {
        $data['age'] = Carbon::parse($data['birth_date'])->age;

//        $age = Carbon::parse($data['birth_date'])->diff(Carbon::now())->y;
//        dd($data,$age);
//        if ($data['age'] != $age) {
//            return Redirect::back()->withInput($data)->withErrors("The birth date and age entered do not match. Please enter a valid age!");
//        }
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required',  'email', 'max:255', 'unique:users', "regex: /^([a-zA-Z0-9_\.\-+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/"],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'experience' => ['required'],
            'qualification' => ['required'],
            'age' => ['required', 'integer', "min:18"],
            'birth_date' => ['required', "before:today"],
            'organization_id' => ['required'],
            'phone' => ['required'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\Models\User
     */
    //save new instructor records
    protected function create(array $data)
    {

        $data['age'] = Carbon::parse($data['birth_date'])->age;
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => 3,
            'status' => 1
        ]);
        Instructor::create([
            'organization_id' => $data['organization_id'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'],
            'age' => $data['age'],
            'qualification' => $data['qualification'],
            'experience' => $data['experience'],
            'user_id' => $user->id,
        ]);
        return $user;
    }

    //show Instructor registration form
    public function showRegistrationForm()
    {

        $organizationAll = Organization::all();
        $ids = [];
        foreach ($organizationAll as $value) {
            $totalInstructor = Instructor::where('organization_id', $value->id)->count();

            if ($totalInstructor < $value->no_of_instructor) {
                $ids[] = $value->id;

            }

        }
        $organization = Organization::whereIn('id', $ids)->pluck('name', 'id');

//        dd($organizations);
        return view('instructors.auth.register')->with('organization', $organization);
    }
}
