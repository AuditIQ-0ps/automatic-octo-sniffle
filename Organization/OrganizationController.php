<?php

namespace App\Http\Controllers\Organization;

use App\Classes\Helpers\Helper as mainHelper;
use App\Classes\Helpers\Roles\Helper as HelperRoles;
use App\Classes\Models\Category\Category;
use App\Exports\CSVExport;
use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PaymentStyle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\Facades\Image;
use Response;

class OrganizationController extends Controller
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

    //display all organization records in admin panel
    public function index(Request $request)
    {
        $per_page = $request->par_page ?? 10;
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'id';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $organizationName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";
        $paymentStyle = PaymentStyle::pluck('style', 'id');
        $organization = Organization::orderby($sortedBy, $sortedOrder);
        $organizationSubscription=OrganizationSubscription::orderby('id');

        if ($request->name) {
            $organization = $organization->where('name', 'like', '%' . $data['name'] . '%');
        }

        if ($request->phone) {
            $organization = $organization->where('phone', 'like', '%' . str_replace("-", "", $request->phone) . '%');
        }
        if($request->organization_status){
            $organization = $organization->whereIn('id',function ($query) use($request){
                $query->select('organization_id')->from('organization_subscriptions')->where('status',$request->organization_status);
            });
        }

        $organization = $organization->whereHas('user', function ($q) use ($request) {
            if ($request->ownname) {
                $q->where(DB::raw('concat(first_name," ",last_name)'), 'like', '%' . $request->ownname . '%');
            }
            if ($request->email) {
                $q->where('email', 'like', '%' . $request->email . '%');
            }
        });
        if ($request->from) {
            $organization = $organization->whereHas('user',function ($q) use ($request){
                $q->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
            });
        }
        if ($request->to) {
            $organization = $organization->whereHas('user',function ($q) use ($request){
                $q->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
            });
        }

        switch($request->input('status')){
            case 'Month':
                $organization = $organization->whereMonth('created_at', \Carbon\Carbon::now()->month);
                break;
            case 'Week':
                $organization = $organization->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'Today':
                $organization = $organization->whereDate('created_at', \Carbon\Carbon::now()->toDate());
                break;
            case 'Inactive':
                $organization = $organization->whereIn('user_id', function ($query) {
                    $query->select('id')->from('users')->where('role_id', '2')->where('status', '0');
                });
                break;
        }

        $organizationClone = new Organization();
        $organization1 = clone $organization;
        $organizationCount = $organization1->count();
        $organization_all = clone $organizationClone;
        $record['All'] = $organization_all->count();
        $organization_month = clone $organizationClone;
        $record['Month'] = $organization_month->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
        $organization_week = clone $organizationClone;
        $record['Week'] = $organization_week->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $organization_today = clone $organizationClone;
        $record['Today'] = $organization_today->whereDate('created_at', \Carbon\Carbon::now()->toDate())->count();
        $organization_inactive = clone $organizationClone;
        $record['Inactive'] = $organization_inactive->whereIn('user_id', function ($query) {
            $query->select('id')->from('users')->where('role_id', '2')->where('status', '0');
        })->count();
        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $organization = $organization->paginate($request->par_page);
            }
            return view('organization.table', compact('organizationCount', 'organization', 'userId',/*'userDropDownList',*/ 'organizationName', 'sortedBy', 'sortedOrder', 'paymentStyle'/*, 'recordStart', 'categories', 'paging', 'totalRecordCount'*/))
                ->with('per_page', $per_page)->with($record);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $organizationDatas = $organization->with('user')->get();
                $rows = ([
                    'id' => 'Organization ID',
                    'name' => 'Organization Name',
                    'email' => 'Organization Email',
                    'phone' => 'Phone',
                    'owner' => 'Owner Name',
                    'created_at' => 'Join On',
                ]);
                $keys = array_keys($rows);
                $txtop = '';
                foreach ($organizationDatas as $organizationData) {
                    $cuRecord = [
                        'id' => $organizationData->id,
                        'name' => $organizationData->name,
                        'email' => $organizationData->user->email,
                        'phone' => $organizationData->mobile,
                        'owner' => $organizationData->user->first_name,
                        'created_at' => $organizationData->created_at,
                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Organizations') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
//                    dd($output);
                    $fileName = exportFilePrefix('Organizations') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $organization = $organization->latest()->paginate($per_page);
            return view('organization.table')
                ->with('organization', $organization)->render();
        }
        $organization = $organization->paginate($per_page);
        return view('organization.index', compact('organizationCount', 'organization', 'userId',/*'userDropDownList',*/
            'organizationName', 'sortedBy', 'sortedOrder', 'paymentStyle'/*, 'recordStart', 'categories', 'paging', 'totalRecordCount'*/))->with($record);
    }

    //create new organization from admin panel
    public function saveAjax(Request $request)
    {
        $data = $request->all();
//        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
//           dd("sd");
//        }
//        else{
//            dd(2);
//        }
//        echo 'regex: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/|unique:users,email'.($request->id ? ",".$request->id : "");die;
        $organizationDate = Organization::where('id', $data['id'])->first();
        $validate = $request->validate([
            'email' => 'regex: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/|unique:users,email' . (($organizationDate->user_id ?? false) ? "," . $organizationDate->user_id : ""),
        ]);

        if ($data['id'] != 0) {

            $organization = Organization::where('id', $data['id'])->update([
                'name' => $data['name'],
                'description' => $data['description'],
//                   'price' => $data['price'],
                'phone' => $data['phone'],
                'mobile' => $data['mobile'],
                'no_of_instructor' => $data['no_of_instructor'],
//                'withdrawal_date' => $data['withdrawal_date'],
                'payment_style_id' => $data['payment_style_id'],
//                'build_year' => $data['build_year'],
            ]);

            User::where('id', $organizationDate['user_id'])->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email']
            ]);
            $response = [];
            $response['success'] = true;
            $response['message'] = 'Organization updated successfully!';
            return response()->json($response);
        } else {
            $data['password'] = Hash::make($data['password']);
            $data['role_id'] = 2;
            $user = User::create($data);
            $data['user_id'] = $user->id;
            $results = Organization::create($data);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Organization created Successfully!';
            return response()->json($response);
        }
    }

    public function getDataForEditModel(Request $request)
    {
        $data = $request->all();
        $results = Organization::with('user', 'paymentStyle')->find($data['id']);
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

    public function viewData(Request $request)
    {
        $data = $request->all();
        dd($data['id']);
    }

    //delete selected organization from admin panel
    public function delete(Request $request)
    {
        $data = $request->all();
        if (empty($data['id'])) {
            return abort(404);
        }

        $isDelete = Organization::where('id', $data['id'])->first();
        if (!empty($isDelete) && $isDelete->delete()) {
            $request->session()
                ->flash('success', 'Organization deleted successfully.');
        } else {
            $request->session()
                ->flash('error', 'Oops, the organization was not deleted. Please try again.');
        }
        return Redirect::back();
    }

    // display selected organization profile records
    public function profile($id)
    {
        $organization = Organization::with('user', 'paymentStyle','subscription')
            ->withCount('students')
            ->withCount('instructors')
            ->where('id', $id)
            ->first();
        return view('organization.profile.index', compact('organization'));
    }

    //save selected organization records
    public function profileSave(Request $request)
    {
        $data = $request->all();
        if(auth()->user()->role_id==1)
        {}
        $request->validate([
            'email' => 'unique:users,email' . ($request->user_id ? "," . $request->user_id : ""),
        ]);

        Organization::where('id', $data['id'])->update(['name'=>$data['name'],'phone' => $data['phone'],'Description'=>$data['description']
            ,'no_of_instructor'=>$data['no_of_instructor'],'mobile'=>$data['mobile'],'payment_style_id'=>$data['payment_style_id']]);
        $result = User::where('id', $data['user_id'])->update(['first_name' => $data['first_name'], 'last_name' => $data['last_name'],
            'email' => $data['email'],'photo'=>$data['emoji-img']]);

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
            return Redirect::back()->withErrors($result);
        }
    }

   //save selected organization changed password
    public function changePassword(Request $request)
    {
        $data = $request->all();
        if ($data['password'] != $data['password_confirmation']) {
            return Redirect::back()->withInput($data)->withErrors(['password_confirmation'=>'Sorry, the 2 passwords did not match, please match them and submit again.']);
        }
        $result = User::where('id', $data['user_id'])->update(['password' => Hash::make($data['password'])]);
        if (!empty($result)) {
            $request->session()->flash('success', "Organization password updated successfully.");
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }
}
