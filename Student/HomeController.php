<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    //display data in user home page
    public function index()
    {
        $user = Auth::user();
        $user->load('student.organization');
        updateUserInFirebase();
        $data['instructorCount'] = Instructor::where('organization_id', $user->student->organization->id)->count();
//        dd($organizationCount);
        $data['instructor'] = User::with('instructor')->whereHas("instructor", function ($q) use ($user) {
            $q->where('organization_id', $user->student->organization->id);
        })->where('role_id', 3)->take(4)->orderBy('id', 'DESC')->get();

        $data['upcomeEventsCount'] = Event::whereHas('user.instructor', function ($q) use ($user) {
            $q->where('organization_id', $user->student->organization->id);
        })->whereDate('date', '>=', date('Y-m-d'))->orderBy('id', 'desc')->count();
        $data['pastEventsCount'] = Event::whereHas('user.instructor', function ($q) use ($user) {
            $q->where('organization_id', $user->student->organization->id);
        })->whereDate('date', '<', date('Y-m-d'))->orderBy('id', 'desc')->count();
        $data['upcomeEventsList'] = Event::whereHas('user.instructor', function ($q) use ($user) {
            $q->where('organization_id', $user->student->organization->id);
        })->whereDate('date', '>=', date('Y-m-d'))->with('user')->orderBy('date', 'asc')->orderBy('start_at', 'asc')->limit(6)->get();

        $data['quizList'] = Quiz::with('questions', 'user')->whereHas('user.instructor', function ($q) use ($user) {
            $q->where('organization_id', $user->student->organization->id);
        })->whereHas('questions')->orderby('id', 'desc')->limit(6)->get();;

        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentMonth1 = date('M');
        $currentdate = date('d');
        for ($i = 1; $i <= date('t'); $i++) {
            $instructor = User::with('instructor')->whereHas("instructor", function ($q) use ($user, $currentMonth, $i, $currentYear) {

                $q->where('organization_id', $user->student->organization->id)->whereMonth('created_at', '=', $currentMonth)
                    ->whereDay('created_at', '=', $i)
                    ->whereYear('created_at', '=', $currentYear);
            })->where('role_id', 3)->count();

            $instructorCount[] = $instructor;
            $Lables[] = $currentMonth1 . ' ' . $i;
        }
        for ($i = 1; $i <= date('t'); $i++) {

            $event = Event::whereHas('user.instructor', function ($q) use ($user) {
                $q->where('organization_id', $user->student->organization->id);
            })->whereMonth('date', '=', $currentMonth)
                ->whereDay('date', '=', $i)
                ->whereYear('date', '=', $currentYear)->count();

            $eventCount[] = $event;
            $Lables1[] = $currentMonth1 . ' ' . $i;
        }
        $data['lables1'] = $Lables1 ?? [];
        $data['lables'] = $Lables ?? [];
        $data['instructorList'] = $instructorCount ?? [];
        $data['eventList'] = $eventCount ?? [];
        return view('students.home')->with($data);
    }

    //view user profile
    public function profile()
    {
        $student = User::with('student.organization')->where('id', auth()->id())->first();
        return view('students.profile', compact('student'));
    }
    // update user profile records
    public function saveProfile(Request $request)
    {
        $data = $request->all();
//        dd($data);
        $result = User::where('id', auth()->id())->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email']
        ]);

        Student::where('user_id', \auth()->id())->update([
            'phone' => $data['phone'],
            'mobile' => $data['mobile']
        ]);

        if ($data['emoji-img']) {
            $path = $data['emoji-img'];
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $img = $base64;
            $folderPath = "images/"; //path location

            $image_parts = explode(";base64,", $img);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $uniqid = time();
            $file = public_path() . '/' . $folderPath . $uniqid . '.' . $image_type;
            $filename = $uniqid . '.' . $image_type;
            file_put_contents($file, $image_base64);

            $photo = '/public/images' . '/' . $filename;
            $result = User::where('id', auth()->id())->update(['photo' => $photo]);
        }

//        if ($request->hasFile('photo')) {
//            $image = $request->file('photo');
//            $path = public_path('images');
//            echo $path . "<br>";
//            $filename = time() . '.' . $image->extension();
//            $image->move($path, $filename);
//            $photo = '/public/images' . '/' . $filename;
//            $result = User::where('id', auth()->id())->update(['photo' => $photo]);
//        }
        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->extension();
            $image=Image::make($image->path())->resize(200,200);
            $path = public_path('/images' . '/' . $filename);
            $image->save($path,50);

            $photo = '/public/images' . '/' . $filename;
            $result = User::where('id', $data['user_id'])->update(['photo' => $photo]);
        }

        if (!empty($result)) {
            $request->session()->flash('success', 'User profile updated successfully.');
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }

    // update user changed password
    public function changePassword(Request $request)
    {
        $data = $request->all();
        if ($data['password'] != $data['password_confirmation']) {
            return Redirect::back()->withInput($data)->withErrors(['password_confirmation'=>'Sorry, the 2 passwords did not match, please match them and submit again.']);
        }
        $result = User::where('id', $data['user_id'])->update(['password' => Hash::make($data['password'])]);
        if (!empty($result)) {
            $request->session()->flash('success', "User password updated successfully.");
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }
}
