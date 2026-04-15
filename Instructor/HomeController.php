<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;

use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
    //display data in instructor home page
    public function index()
    {
        $user = Auth::user();
        $user->load('instructor.organization');
        updateUserInFirebase();
        $data['studentCount'] = Student::where('organization_id', $user->instructor->organization->id)->count();
        $data['student'] = User::with('student')->whereHas("student", function ($q) use ($user) {
            $q->where('organization_id', $user->instructor->organization->id);
        })->where('role_id', 4)->take(6)->orderBy('id', 'DESC')->get();
        $data['upcomeEventsCount'] = Event::where('user_id', $user->id)->whereDate('date', '>=', date('Y-m-d'))->orderBy('id', 'desc')->count();
        $data['pastEventsCount'] = Event::where('user_id', $user->id)->whereDate('date', '<', date('Y-m-d'))->orderBy('id', 'desc')->count();
        $data['upcomeEventsList'] = Event::where('user_id', $user->id)->whereDate('date', '>=', date('Y-m-d'))->orderBy('date', 'asc')->orderBy('start_at', 'asc')->limit(6)->get();
        $data['pastEventsList'] = Event::where('user_id', $user->id)->whereDate('date', '<', date('Y-m-d'))->orderBy('date', 'asc')->orderBy('start_at', 'asc')->limit(6)->get();
        $data['quizList']=Quiz::where('user_id',$user->id)->orderBy('id','desc')->limit(6)->get();
//        $data['quizList'] = Question::with('questionOptions')->where('user_id', $user->id)->orderby('id', 'desc')->limit(4)->get();;
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentMonth1 = date('M');
        $currentdate = date('d');
        $date = \Carbon\Carbon::today()->subDays(7);
        $lastStartOfWeek = \Illuminate\Support\Carbon::parse($date)->startOfWeek();
        $lastEndOfWeek = Carbon::parse($date)->endOfWeek();

        $organization_id=Instructor::where('user_id',$user->id)->pluck('organization_id');
        $studentCounts['total'] = Student::whereHas('user')->whereHas('organization')->with('organization')->where('organization_id',$organization_id)->count();
        $studentCounts['weekly'] = Student::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $studentCounts['last_week'] = Student::whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $studentCounts['percent'] = (($studentCounts['weekly'] - $studentCounts['last_week']) * 100) / ($studentCounts['last_week'] ?: 1);
        $studentCounts['percent'] = number_format($studentCounts['percent']);
//        dd($studentCounts);

        //salesforce
        $salesFroce['total']=Student::where('organization_id',$organization_id)->count();
        $salesFroce['weekly']=Student::where('organization_id',$organization_id)->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $salesFroce['last_week']=Student::where('organization_id',$organization_id)->whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $salesFroce['percent']=(($salesFroce['weekly'] - $salesFroce['last_week']) * 100) / ($salesFroce['last_week'] ?: 1);
        $salesFroce['percent'] = number_format($salesFroce['percent']);
//        dd($salesFroce);

        //livestream
        $allEvents['total'] = Event::where('user_id',$user->id)->count();
        $allEvents['weekly'] = Event::where('user_id',$user->id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $allEvents['last_week'] = Event::where('user_id',$user->id)->whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $allEvents['percent'] = (($allEvents['weekly'] - $allEvents['last_week']) * 100) / ($allEvents['last_week'] ?: 1);
        $allEvents['percent'] = number_format($allEvents['percent']);
//        dd($allEvents);

        $allQuiz['total']=Quiz::where('user_id',$user->id)->count();
        $allQuiz['weekly']=Quiz::where('user_id',$user->id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $allQuiz['last_week'] = Quiz::where('user_id',$user->id)->whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $allQuiz['percent'] = (($allQuiz['weekly'] - $allQuiz['last_week']) * 100) / ($allQuiz['last_week'] ?: 1);
        $allQuiz['percent'] = number_format($allQuiz['percent']);
//        dd($allQuiz);
        for ($i = 1; $i <= date('t') ; $i++) {
            $student = User::with('student')->whereHas("student", function ($q) use ($user, $currentMonth, $i, $currentYear) {

                $q->where('organization_id', $user->instructor->organization->id)->whereMonth('created_at', '=', $currentMonth)
                    ->whereDay('created_at', '=', $i)
                    ->whereYear('created_at', '=', $currentYear);
            })->where('role_id', 4)->count();

            $studentCount[] = $student;
            $Lables[] = $currentMonth1 . ' ' . $i ;
        }
        for ($i = 1; $i <= date('t') ; $i++) {

            $event = Event::where('user_id', $user->id)->whereMonth('date', '=', $currentMonth)
                ->whereDay('date', '=', $i)
                ->whereYear('date', '=', $currentYear)->count();

            $eventCount[] = $event;
            $Lables1[] = $currentMonth1 . ' ' . $i ;
        }
        $data['lables1'] = $Lables1 ?? [];
        $data['lables'] = $Lables ?? [];
        $data['studentList'] = $studentCount ?? [];
        $data['eventList'] = $eventCount ?? [];
//        return $data;
        return view('instructors.home')
            ->with('allEvents',$allEvents)
            ->with('studentCounts',$studentCounts)
            ->with('salesFroce',$salesFroce)
            ->with('allQuiz',$allQuiz)
            ->with($data);
    }

    //view instructor profile
    public function profile()
    {
        $instructor = User::with('instructor.organization')->where('id', auth()->id())->first();
        return view('instructors.profile', compact('instructor'));
    }

    // update instructor profile records
    public function saveProfile(Request $request)
    {
        $data = $request->all();
//        dd($data);
        $request->validate([
            'birth_date' => 'before:today',
            'email' => 'regex: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,3})$/'
        ]);
        if( $data['birth_date']==null){
            $data['birth_date']='';
        }
        else {
            $data['birth_date'] = Carbon::parse($data['birth_date'])->format('Y-m-d');
        }
        $age = Carbon::parse($data['birth_date'])->diff(Carbon::now())->y;
//        dd($data,$age);
        if ($data['age'] != $age) {
            return Redirect::back()->withInput($data)->withErrors("The birth date and age entered do not match. Please enter a valid age!");
        }
        $result= User::where('id',auth()->id())->update([
            'first_name'=>$data['first_name'],
            'last_name'=>$data['last_name'],
            'email'=>$data['email']
        ]);

        Instructor::where('user_id',\auth()->id())->update([
            'phone'=>$data['phone'],
            'birth_date'=>$data['birth_date'],
            'age'=>$data['age'],
            'experience'=>$data['experience'],
            'qualification'=>$data['qualification']
        ]);

        if ($data['emoji-img'])
        {
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
            $file = public_path().'/'. $folderPath . $uniqid . '.'.$image_type;
            $filename=$uniqid . '.'.$image_type;
            file_put_contents($file, $image_base64);

            $photo = '/public/images' . '/' . $filename;
            $result = User::where('id', auth()->id())->update(['photo' => $photo]);
        }

//        if ($request->hasFile('photo')) {
//            $image = $request->file('photo');
//            $path = public_path('images');
//
//            echo $path . "<br>";
//            $filename = time() . '.' . $image->extension();
//            $image->move($path, $filename);
//            $photo = '/public/images' . '/' . $filename;
////            dd($photo);
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

        if ( !empty($result)  ) {
            $request->session()->flash( 'success', 'Instructor profile updated successfully.' );
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }

    // update instructor password
    public function changePassword(Request $request)
    {
        $data = $request->all();
        if ($data['password'] != $data['password_confirmation']) {
            return Redirect::back()->withInput($data)->withErrors(['password_confirmation'=>'Sorry, the 2 passwords did not match, please match them and submit again.']);
        }
        $result = User::where('id', $data['user_id'])->update(['password' => Hash::make($data['password'])]);
        if (!empty($result)) {
            $request->session()->flash('success', "Instructor password updated successfully.");
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }

}
