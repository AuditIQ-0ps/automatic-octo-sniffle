<?php

namespace App\Http\Controllers\Instructor;

use App\Classes\Helpers\Helper as mainHelper;
use App\Classes\Helpers\Roles\Helper as HelperRoles;
use App\Classes\Models\Category\Category;
use App\Exports\CSVExport;
use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\Facades\Image;
use Response;

class InstructorController extends Controller
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

    //
    public function index(Request $request)
    {
//        $instructors =new Instructor();
        $par_page = $request->par_page ?? 10;
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'ID';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $instructorName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $organzationId = Organization::pluck('name', 'id');
        $organization_id = Organization::where('user_id', \auth()->id())->pluck('id')->first();

        $instructors = Instructor::orderby($sortedBy, $sortedOrder)->with('user', 'organization');
        $instructors = $instructors->whereHas('user', function ($query) use ($request) {
            if ($request->name) {
                $query->where(DB::raw('concat(first_name," ",last_name)'), 'like', '%' . $request->name . '%');
            }
            if ($request->email) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }
        });
        if ($request->phone) {
//            dd(str_replace("-","", $request->phone));
            $instructors = $instructors->where('phone', 'like', '%' . str_replace("-", "", $request->phone) . '%');
        }
        $instructors = $instructors->whereHas('organization', function ($query) use ($request) {
            if ($request->orgname) {
                $query->where('name', 'like', '%' . $request->orgname . '%');
            }
        });
//        if ($request->from) {
//            $instructors = $instructors->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
//        }
//        if ($request->to) {
//            $instructors = $instructors->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
//        }
//        if (\auth()->user()->role_id == 1) {
//            $layoutType = 'admin';
//
//        }
//        if (\auth()->user()->role_id == 2) {
//            $layoutType = 'organization';
//            $instructors = $instructors->where('organization_id', $organization_id);
//        }
        if ($request->Month) {
            $instructors = $instructors->whereMonth('created_at', \Carbon\Carbon::now()->month);
        }
        if ($request->from) {
            $instructors = $instructors->whereHas('user',function ($q) use ($request){
                $q->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
            });
        }
        if ($request->to) {
            $instructors = $instructors->whereHas('user',function ($q) use ($request){
                $q->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
            });
//            $organization = $organization->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }

        if($request->instructor_status){
//            dd($request->instructor_status);
            $instructors = $instructors->whereIn('user_id', function ($query) use($request) {
                $query->select('id')->from('users')->where('role_id', '3')->where('status','=', $request->instructor_status);
            });
        }
        switch($request->input('status')){
            case 'Month':
                $instructors = $instructors->whereMonth('created_at', \Carbon\Carbon::now()->month);
                break;
            case 'Week':
                $instructors = $instructors->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'Today':
                $instructors = $instructors->whereDate('created_at', \Carbon\Carbon::now()->toDate());
                break;
            case 'Inactive':
                $instructors = $instructors->whereIn('user_id', function ($query) {
                    $query->select('id')->from('users')->where('role_id', '3')->where('status', '0');
                });
                break;
        }
//        if (\auth()->id()==1)
//        {
//            $layoutType='admin';
//        }
//        $instructors= Instructor::join('users','users.id','instructors.user_id')->orderby('instructors.id', 'desc')->paginate(10);
//        dd($instructors);
        if(\auth()->user()->role_id == 1){
            $layoutType = 'admin';
            $instructorsClone = new Instructor();
            $instructors1 = clone $instructors;
            $instructorsCount = $instructors1->count();
            $instructors_all = clone $instructorsClone;
            $record['All'] = $instructors_all->count();
            $instructors_month = clone $instructorsClone;
            $record['Month'] = $instructors_month->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
            $instructorsn_week = clone $instructorsClone;
            $record['Week'] = $instructorsn_week->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
            $instructors_today = clone $instructorsClone;
            $record['Today'] = $instructors_today->whereDate('created_at', \Carbon\Carbon::now()->toDate())->count();
            $instructors_inactive = clone $instructorsClone;
            $record['Inactive'] = $instructors_inactive->whereIn('user_id', function ($query) {
                $query->select('id')->from('users')->where('role_id', '3')->where('status', '0');
            })->count();
        }
            if(\auth()->user()->role_id == 2) {
                $layoutType = 'organization';
                $instructors = $instructors->where('organization_id', $organization_id);
                $instructorsClone = new Instructor();
                $instructors1 = clone $instructors;
                $instructorsCount = $instructors1->where('organization_id',$organization_id)->count();
                $instructors_all = clone $instructorsClone;
                $record['All'] = $instructors_all->where('organization_id',$organization_id)->count();
                $instructors_month = clone $instructorsClone;
                $record['Month'] = $instructors_month->where('organization_id',$organization_id)->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
                $instructorsn_week = clone $instructorsClone;
                $record['Week'] = $instructorsn_week->where('organization_id',$organization_id)->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
                $instructors_today = clone $instructorsClone;
                $record['Today'] = $instructors_today->where('organization_id',$organization_id)->whereDate('created_at', \Carbon\Carbon::now()->toDate())->count();
                $instructors_inactive = clone $instructorsClone;
                $record['Inactive'] = $instructors_inactive->where('organization_id',$organization_id)->whereIn('user_id', function ($query) {
                    $query->select('id')->from('users')->where('role_id', '3')->where('status', '0');
                })->count();
            }

        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $instructors = $instructors->paginate($request->par_page);
            }
            return view('instructors.table', compact('instructorsCount', 'instructors', 'userId', 'organzationId', 'instructorName',
                'layoutType', 'sortedBy', 'sortedOrder'))->with('per_page', $par_page)->with($record);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $instructorsDatas = $instructors->get();
                $rows = ([
                    'id' => 'Instructor ID',
                    'name' => 'Instructor Name',
                    'email' => 'Instructor Email',
                    'phone' => 'Phone',
                    'organization_name' => 'Organization Name',
                    'created_at' => 'Join On',
                ]);
                $recodes = [];
                $txtop = '';
                foreach ($instructorsDatas as $instructorsData) {
                    $cuRecord = [
                        'id' => $instructorsData->id,
                        'name' => $instructorsData->user->name,
                        'email' => $instructorsData->user->email,
                        'phone' => $instructorsData->phone,
                        'organization_name' => $instructorsData->organization->name,
                        'created_at' => $instructorsData->created_at,
                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Instructors') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
                    $fileName = exportFilePrefix('Instructors') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $instructors = $instructors->latest()->paginate($par_page);
            return view('instructors.table')->with('per_page', $par_page)
                ->with('instructors', $instructors)->render();
        }
        $instructors = $instructors->paginate($par_page);
//        dd($instructors);
        return view('instructors.index', compact('instructorsCount', 'instructors', 'userId', 'organzationId', 'instructorName',
            'layoutType', 'sortedBy', 'sortedOrder'))->with($record);
    }

    public function saveAjax(Request $request)
    {
        $data = $request->all();
        $request->validate([
            'email' => 'regex: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,3})$/|unique:users,email' . ($data['id'] != 0 ? "," . $data['id'] : ""),
//              'password'=>'regex: /^\S*$/',
//              'confirm_password'=>'regex: /^\S*$/'
        ]);
        $organization = Organization::with('user')->where('user_id', auth()->id())->first();

//        $totalInstructor = Instructor::where('organization_id', $organization['id'])->count();
//
//        if ($totalInstructor >= $organization['no_of_instructor']) {
//            $response['message'] = 'Sorry, you cannot add more than ' . $organization['no_of_instructor'] . ' instructors';
//            return response()->json($response);
//        }
//        dd($totalInstructor,$organization['id'],$organization['no_of_instructor']);
        $data['organization_id'] = $organization['id'];


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
        $layoutType = '';
        if (\auth()->user()->role_id == 1) {
            $layoutType = 'admin';
        }
        if (\auth()->user()->role_id == 2) {
            $layoutType = 'organization';
        }
        $instructor = Instructor::with(['user','organization'=>function($q){
            $q->withCount('students');
        }])->whereHas('user')->where('id', $id)->first();
//        dd($instructor);
        if (!$instructor) {
            return \redirect(route('admin.instructor'))->withErrors(["errors" => "Sorry, that instructor could not be found!"]);
        }
        return view('instructors.profile.index', compact('instructor', 'layoutType'));
    }

    public function profileSave(Request $request)
    {
        $data = $request->all();
//        dd($data);
        if(auth()->user()->role_id!=1) {
            $request->validate([

                'birth_date' => 'required|before:today',
                'age' => 'numeric|min:1',
                'email' => 'regex: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,3})$/|unique:users,email' . ($request->user_id ? "," . $request->user_id : ""),
            ]);
        }
        if( $data['birth_date']==null){
            $data['birth_date']='';
        }
        else {
            $data['birth_date'] = Carbon::parse($data['birth_date'])->format('Y-m-d');
        }
        $age = Carbon::parse($data['birth_date'])->diff(Carbon::now())->y;
        if ($data['age'] != $age) {
            return Redirect::back()->withInput($data)->withErrors("The birth date and age entered do not match. Please enter a valid age!");
        }
//        dd($data['birth_date']);
//        dd($data['birth_date']);
        Instructor::where('id', $data['id'])->update(['phone' => $data['phone'], 'birth_date' => $data['birth_date'], 'age' => $data['age'],
            'qualification' => $data['qualification'], 'experience' => $data['experience']]);
        $result = User::where('id', $data['user_id'])->update(['first_name' => $data['first_name'],'photo'=>$data['emoji-img'], 'last_name' => $data['last_name'],
        'email' => $data['email'], 'status' => $data['status']]);
//        if ($request->hasFile('photo')) {
//            $image = $request->file('photo');
//            $path = public_path('images');
//            echo $path . "<br>";
//            $filename = time() . '.' . $image->extension();
//            $image->move($path, $filename);
//            $photo = '/public/images' . '/' . $filename;
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
            $request->session()->flash('success', 'Instructor profile updated successfully.');
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result);
        }
    }

    public function organizationView()
    {
        $organization = Instructor::with('organization')->where('user_id', \auth()->id())->first();
//       dd($organization);
        return view('instructors.organizationIndex', compact('organization'));
    }

    public function changePassword(Request $request)
    {
        $data = $request->all();
        if ($data['password'] != $data['password_confirmation']) {
            return Redirect::back()->withInput($data)->withErrors(['password_confirmation'=>'Sorry, the 2 passwords did not match, please match them and submit again.']);
        }
//        dd($data);
        $result = User::where('id', $data['user_id'])->update(['password' => Hash::make($data['password'])]);
        if (!empty($result)) {
            $request->session()->flash('success', "Instructor password updated successfully.");
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }
}
