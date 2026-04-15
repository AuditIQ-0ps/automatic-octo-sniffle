<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
    //display data in organization home page
    public function index()
    {
        $user = Auth::user();
        $user->load('organization');
        updateUserInFirebase();
        $data['instructorCount'] = Instructor::where('organization_id', $user->organization->id)->count();
        $data['studentCount'] = Student::where('organization_id', $user->organization->id)->count();

        $data['instructor'] = User::with('instructor')->whereHas('instructor', function ($q) use ($user) {
            $q->where('organization_id', $user->organization->id);
        })->where('role_id', 3)->take(6)->orderBy('id', 'DESC')->get();


        $data['student'] = User::with('student')->whereHas("student", function ($q) use ($user) {
            $q->where('organization_id', $user->organization->id);
        })->where('role_id', 4)->take(6)->orderBy('id', 'DESC')->get();

        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentMonth1 = date('M');
        $currentdate = date('d');
        $date = \Carbon\Carbon::today()->subDays(7);
        $lastStartOfWeek = Carbon::parse($date)->startOfWeek();
        $lastEndOfWeek = Carbon::parse($date)->endOfWeek();


        //   instructor counts
        $instructorCounts['total'] = Instructor::whereHas('user')->whereHas('organization')->with('organization')->where('organization_id', $user->organization->id)->count();
        $instructorCounts['weekly'] = Instructor::where('organization_id', $user->organization->id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $instructorCounts['last_week'] = Instructor::where('organization_id', $user->organization->id)->whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $instructorCounts['percent'] = (($instructorCounts['weekly'] - $instructorCounts['last_week']) * 100) / ($instructorCounts['last_week'] ?: 1);
        $instructorCounts['percent'] = number_format($instructorCounts['percent']);
//        dd($instructorCounts);

        //student Counts
        $studentCounts['total'] = Student::whereHas('user')->whereHas('organization')->with('organization')->where('organization_id', $user->organization->id)->count();
        $studentCounts['weekly'] = Student::where('organization_id', $user->organization->id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $studentCounts['last_week'] = Student::where('organization_id', $user->organization->id)->whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $studentCounts['percent'] = (($studentCounts['weekly'] - $studentCounts['last_week']) * 100) / ($studentCounts['last_week'] ?: 1);
        $studentCounts['percent'] = number_format($studentCounts['percent']);
//        dd($studentCounts);

        //salesforce
        $salesFroce['total'] = $studentCounts['total'] + $instructorCounts['total'];
        $salesFroce['weekly'] = $studentCounts['weekly'] + $instructorCounts['weekly'];
//        dd($salesFroce);

        //livestream
        $instructor_id = Instructor::where('organization_id', $user->organization->id)->pluck('user_id');
        $allEvents['total'] = Event::whereIn('user_id', $instructor_id)->count();
        $allEvents['weekly'] = Event::whereIn('user_id', $instructor_id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $allEvents['last_week'] = Event::whereIn('user_id', $instructor_id)->whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $allEvents['percent'] = (($allEvents['weekly'] - $allEvents['last_week']) * 100) / ($allEvents['last_week'] ?: 1);
        $allEvents['percent'] = number_format($allEvents['percent']);
//        dd($allEvents);

        for ($i = 1; $i <= date('t'); $i++) {
            $student = User::with('student')->whereHas("student", function ($q) use ($user, $currentMonth, $i, $currentYear) {

                $q->where('organization_id', $user->organization->id)->whereMonth('created_at', '=', $currentMonth)
                    ->whereDay('created_at', '=', $i)
                    ->whereYear('created_at', '=', $currentYear);
            })->where('role_id', 4)->count();
            $studentCount[] = $student;

            $instructor = User::with('instructor')->whereHas("instructor", function ($q) use ($user, $currentMonth, $i, $currentYear) {

                $q->where('organization_id', $user->organization->id)->whereMonth('created_at', '=', $currentMonth)
                    ->whereDay('created_at', '=', $i)
                    ->whereYear('created_at', '=', $currentYear);
            })->where('role_id', 3)->count();

            $instructorCount[] = $instructor;
            $Lables[] = $currentMonth1 . ' ' . $i;
        }
        $data['instructorList'] = $instructorCount ?? [];
        $data['lables'] = $Lables ?? [];
        $data['studentList'] = $studentCount ?? [];
//        return $data;
        return view('organization.home')
            ->with('instructorCounts', $instructorCounts)
            ->with('studentCounts', $studentCounts)
            ->with('allEvents', $allEvents)
            ->with('salesFroce', $salesFroce)
            ->with($data);
    }

    //view organization profile
    public function profile()
    {
        $organization = User::with('organization.paymentStyle')->where('id', auth()->id())->first();
        return view('organization.profile', compact('organization'));
    }

    // update and save organization profile records
    public function saveProfile(Request $request)
    {
        $data = $request->all();
//        dd($data);

        $request->validate([
            'email' => 'required|regex: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,3})$/|unique:users,email' . ($request->user_id ? "," . $request->user_id : ""),
        ]);
        $result = User::where('id', auth()->id())->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email']
        ]);

        Organization::where('user_id', \auth()->id())->update([
            'phone' => $data['phone'],
            'mobile' => $data['mobile'],
            'description' => $data['description'],
            'no_of_instructor' => $data['no_of_instructor'],
//            'build_year'=>$data['build_year']
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
            $request->session()->flash('success', 'Organization profile updated successfully.');
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }

    //save organization password
    public function changePassword(Request $request)
    {
        $data = $request->all();
        if ($data['password'] != $data['password_confirmation']) {
            return Redirect::back()->withInput($data)->withErrors(['password_confirmation'=>'Sorry, the 2 passwords did not match, please match them and submit again.']);
        }
        $result = User::where('id', $data['user_id'])->update(['password' => Hash::make($data['password'])]);
        if (!empty($result)) {
            $request->session()->flash('success', "organization password updated successfully.");
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }
}
