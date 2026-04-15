<?php

namespace App\Http\Controllers\Student;

use App\Classes\Helpers\Helper as mainHelper;
use App\Classes\Helpers\Roles\Helper as HelperRoles;
use App\Classes\Models\Category\Category;
use App\Exports\CSVExport;
use App\Http\Controllers\Controller;
use App\Mail\InviteInstructorMail;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\StreamUser;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\Facades\Image;
use Response;

class StudentController extends Controller
{
    protected $categoryObj;
    protected $userObj;
    protected $_helper;
    protected $_helperRoles;

    public function __construct(Category $categoryModel)
    {
        $this->categoryObj = $categoryModel;
        $this->_helper = new mainHelper();
        $this->_helperRoles = new HelperRoles();
//        $this->userObj = new User();
    }

    public function index(Request $request)
    {
        $orgid=Instructor::where('user_id',\auth()->user()->id)->pluck('organization_id');
        $per_page=$request->par_page??10;
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'id';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $studentName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $organzationId = User::pluck('first_name', 'id');
        $student = User::with(['student.organization'])->whereHas('student')->whereHas('student.organization', function ($query) use ($request) {
            if ($request->orgname)
                $query->where('name', 'like', '%' . $request->orgname . '%');
        });
        $students=Student::with('user','instructor','organization');
        if ($request->name) {
            $student = $student->where(DB::raw('concat(first_name," ",last_name)'), 'like', '%' . $request->name . '%');
        }

        if ($request->email) {
            $student = $student->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->phone) {

            $student = $student->whereHas('student', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . str_replace("-", "", $request->phone) . '%');
            });
        }
        if ($request->from) {
            $student = $student->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $student = $student->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }
        if (\auth()->user()->role_id == 3) {
            $instructor = Instructor::where('user_id', auth()->id())->first();
            $layoutType = 'instructors';

            $student = $student->whereHas('student', function ($q) use ($instructor) {
                $q->where('organization_id', $instructor->organization_id);
            });
//            dd($student->count());
        }
//      elseif (\auth()->user()->role_id == 1) {
//            $layoutType = 'admin';
////            if (isset($data['name'])) {
//        }
        if($request->user_status){
//            dd($request->user_status);
            $student=$student->where('role_id','=','4')->where('status','=',$request->user_status);
        }
        switch($request->input('status')){
            case 'Month':
                $student = $student->whereMonth('created_at',\Carbon\Carbon::now()->month);
                break;
            case 'Week':
                $student = $student->whereBetween('created_at',[\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'Today':
                $student = $student->whereDate('created_at',\Carbon\Carbon::now()->toDate());
                break;
            case 'Inactive':
                $student = Student::whereIn('user_id', function ($query) {
                    $query->select('id')->from('users')->where('role_id', '4')->where('status', '0');
                });
                break;
        }

        $student = $student->orderby($sortedBy, $sortedOrder);

        $studentClone = new Student();
        if(\auth()->user()->role_id == 1) {
            $layoutType = 'admin';
            $student1 = clone $student;
            $studentCount = $student->count();
            $student_all = clone $studentClone;
            $record['All'] = $student_all->count();
            $student_month = clone $studentClone;
            $record['Month'] = $student_month->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
            $student_week = clone $studentClone;
            $record['Week'] = $student_week->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
            $student_today = clone $studentClone;
            $record['Today'] = $student_today->whereDate('created_at', \Carbon\Carbon::now()->toDate())->count();
            $student_inactive = clone $studentClone;
            $record['Inactive'] = $student_inactive->whereIn('user_id', function ($query) {
                $query->select('id')->from('users')->where('role_id', '4')->where('status', '0');
            })->count();
        }
//        dd($record);
        elseif(\auth()->user()->role_id == 3) {
            $instructor = Instructor::where('user_id', auth()->id())->first();
            $layoutType = 'instructors';
            $studentClone = new Student();
            $student1 = clone $student;
            $studentCount = $student->count();
            $student_all = clone $studentClone;
            $record['All'] = $student_all->where('organization_id', $instructor->organization_id)->count();
            $student_month = clone $studentClone;
            $record['Month'] = $student_month->where('organization_id', $instructor->organization_id)->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
            $student_week = clone $studentClone;
            $record['Week'] = $student_week->where('organization_id', $instructor->organization_id)->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
            $student_today = clone $studentClone;
            $record['Today'] = $student_today->where('organization_id', $instructor->organization_id)->whereDate('created_at', \Carbon\Carbon::now()->day)->count();
            $student_inactive = clone $studentClone;
            $record['Inactive'] = $student_inactive->where('organization_id', $instructor->organization_id)->whereIn('user_id', function ($query) {
                $query->select('id')->from('users')->where('role_id', '4')->where('status', '0');
            })->count();

        }
        if($request->isXmlHttpRequest()){
            if(!empty($request->par_page)) {
                $student = $student->paginate($request->par_page);
            }
            return view('students.table', compact('studentCount', 'student', 'userId', 'organzationId',
                'layoutType', 'studentName', 'sortedBy', 'sortedOrder'))->with('per_page',$per_page)->with($record);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
           if(\auth()->user()->role_id=='1'){
                $students=Student::with('user','organization');
           }
           else
               $students=Student::with('user','organization')->where('organization_id',$orgid);
            if ((in_array($request->export, ['csv', 'text']))) {
                $studentDatas = $students->get();
                $rows=([
                    'id' => 'User ID',
                    'name' => 'User Name',
                    'email' => 'User Email',
//                    'phone'=>'Phone',
                    'organization_name' => 'Organization Name',
                    'created_at' => 'Join On',
                ]);
                $recodes = [];
                $txtop = '';
                foreach ($studentDatas as $studentData) {
                    $cuRecord =  [
                        'id' => $studentData->id,
                        'name' => $studentData->user->name,
                        'email' => $studentData->user->email,
//                        'phone'=>$studentData->phone,
                        'organization_name'=>$studentData->organization->name,
                        'created_at' => $studentData->created_at,
                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Users') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
                    $fileName = exportFilePrefix('Users') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }

            $student = $student->latest()->paginate($request->par_page);
            return view('students.table')
                ->with('student', $student)->render();
        }
        $student = $student1->paginate($per_page);
        return view('students.index', compact('studentCount', 'student', 'userId', 'organzationId',
            'layoutType', 'studentName', 'sortedBy', 'sortedOrder'))->with($record);
    }

    public function saveAjax(Request $request)
    {
        $data = $request->all();
        $request->validate([
            'email' => 'unique:users,email' . ($request->user_id ? "," . $request->user_id : ""),
        ]);
        if ($data['password'] != $data['confirm_password']) {
            $response['message'] = 'Sorry, the 2 passwords did not match, please match them and submit again.';
            return response()->json($response);
        }

        if ($data['id'] != 0) {
            $organization = Instructor::where('id', $data['id'])->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price'],
                'phone' => $data['phone'],
                'mobile' => $data['mobile'],
                'no_of_instructor' => $data['no_of_instructor'],
//                'withdrawal_date' => $data['withdrawal_date'],
                'payment_style_id' => $data['payment_style_id'],
//                'build_year' => $data['build_year'],
            ]);

            User::where('id', $organization['user_id'])->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email']
            ]);
        } else {
            $password = $data['password'];
            $data['password'] = Hash::make($data['password']);
            $data['role_id'] = 3;
            $user = User::create($data);
            $data['user_id'] = $user->id;
            $results = Instructor::create($data);

            $details = [
                'title' => 'Login Credentials',
                'name' => $user->name,
                'email' => $data['email'],
                'userType' => "Instructor",
                'password' => $password,
                'type' => "Invitation"
            ];
            dispatch(new \App\Jobs\SendEmailJob($details));
        }

        $response = [];
        $response['success'] = true;
//        $response['message'] = $message;
        return response()->json($response);
    }

    public function getDataForEditModel(Request $request)
    {
        $data = $request->all();
        $results = Instructor::with('user', 'organization')->find($data['id']);
//        dd($results);
        $response = [];
        $response['success'] = false;
        $response['message'] = '';

        if (!empty($results['id']) && $results['id'] > 0) {
            $response['success'] = true;
            $response['message'] = '';
            $response['data'] = $results;
//            dd($response['data']->paymentStyle['style']);
        }
        return response()->json($response);
    }

    public function delete(Request $request)
    {
        $data = $request->all();
        if (empty($data['id'])) {
            return abort(404);
        }
        $instructor = Instructor::where('id', $data['id'])->first();
//        dd($instructor['user_id']);
        $userDelete = User::where('id', $instructor['user_id'])->delete();
        $isDelete = Instructor::where('id', $data['id'])->delete();
        if ($isDelete) {
            $request->session()
                ->flash('success', 'Instructor deleted successfully.');
        } else {
            $request->session()
                ->flash('error', 'Oops, the instructor was not deleted. Please try again.');
        }
        return Redirect::back();
    }
//    public function invite($id)
//    {
//        $instructor=Instructor::with('user')->where('id',$id)->first();
//        dd($instructor['user']['email']);
//        $details = [
//            'title' => 'Login Credentials',
//            'email' => 'This is for testing email using smtp'
//        ];
//    }

    public function profile($id)
    {
        $student = User::with('student', 'student.organization')->whereHas('student')->whereHas('student.organization')->where('id', $id)->first();
//       dd($student);
        $layoutType ='';
        if (\auth()->user()->role_id == 1) {
            $layoutType = 'admin';
        }
        if (\auth()->user()->role_id == 3) {
            $layoutType = 'instructors';
        }
        return view('students.profile.index', compact('student', 'layoutType'));
    }

    public function profileSave(Request $request)
    {
        $data = $request->all();
//        dd($data);
        $request->validate([
            'email' => 'unique:users,email' . ($request->user_id ? "," . $request->user_id : ""),

        ]);
        Student::where('id', $data['id'])->update(['phone' => $data['phone']]);
        $result = User::where('id', $data['user_id'])->update(['first_name' => $data['first_name'],'phone'=>$data['phone'],'photo'=>$data['emoji-img'], 'last_name' => $data['last_name'], 'email' => $data['email']]);
//        dd($data,$request->hasFile('photo'));
//        if ($request->hasFile('photo')) {
//            $image = $request->file('photo');
//            $path = public_path('images');
//            echo $path . "<br>";
//            $filename = time() . '.' . $image->extension();
//            $image->move($path, $filename);
//            $photo = '/public/images' . '/' . $filename;
////            dd(url($photo));
//            $result = User::where('id', $data['user_id'])->update(['photo' => $photo]);
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
            return Redirect::back()->withErrors($result);
        }
    }

    public function getUser(Request $request)
    {
        $data = $request->all();
        $userId = StreamUser::where('peer_id', $data['peer_id'])->orWhere('uid', $request->peer_id)->orderby('id', "desc")->first();

        if (!$userId) {
            $userId = Event::where('peer_id', $data['peer_id'])->orWhere('uid', $request->peer_id)->first();
//            echo "hehehe";
        } else {
            if ($request->left) {
                $userId->delete();
            }
        }
//        return $userId;
        $user = User::where('id', $userId->user_id ?? 0)->first();
        $response['name'] = $user->name ?? "";
        $response['user'] = $user;
        $response['d'] = $userId;
        $response['uid'] = $userId->uid ?? "";
        $response['peer_id'] = $userId->peer_id ?? "";
        return response($response);
    }

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
